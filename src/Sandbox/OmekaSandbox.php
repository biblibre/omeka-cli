<?php

namespace OmekaCli\Sandbox;

use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;
use Opis\Closure\SerializableClosure;

/**
 * Executes code in an Omeka environment without polluting the main process.
 *
 * This allows to execute PHP code that uses Omeka classes and functions inside
 * a child process to avoid polluting the main process.
 * This is needed in order to be able to reload the Omeka environment if needed
 * (after an update of Omeka or one of its plugins for instance)
 */
class OmekaSandbox implements ContextAwareInterface
{
    use ContextAwareTrait;

    const ENV_LONGLIVED = 1;
    const ENV_SHORTLIVED = 2;

    protected $fd = [];
    protected $workerPid;

    /**
     * Terminates the child process and close sockets.
     */
    public function __destruct()
    {
        if ($this->workerPid && getmypid() !== $this->workerPid) {
            if (0 === pcntl_waitpid($this->workerPid, $status, WNOHANG)) {
                // Tell the child to exit
                $closure = new SerializableClosure(function () {
                    exit(0);
                });
                $this->write($this->fd[1], $closure);
                pcntl_waitpid($this->workerPid, $status);
            }
        }

        foreach ($this->fd as $socket) {
            if (is_resource($socket)) {
                socket_close($socket);
            }
        }
    }

    /**
     * Executes a callback inside the sandbox.
     *
     * Callback can be executed in two different environments:
     * - the long-lived environment: Omeka is initialized only once before the
     *   first execution. Subsequent calls to this method will use the same
     *   child. This is the default.
     *   In order to be run in the the child process, the callback has to be
     *   serialized (using SuperClosure). This has some drawbacks, for instance
     *   you cannot use resources that have been opened in the parent process.
     * - the short-lived environment: before every execution a child process is
     *   created and Omeka is initialized in this process.
     *
     * @throws \Exception if an uncaught exception is thrown in the callback
     *
     * @param callable $callback    The callback to execute
     * @param int      $environment ENV_LONGLIVED or ENV_SHORTLIVED
     *
     * @return mixed the value returned by the callback
     */
    public function execute(callable $callback, $environment = self::ENV_LONGLIVED)
    {
        if ($environment === self::ENV_SHORTLIVED) {
            $fd = $this->createSocketPair();
            $pid = $this->fork();
            if ($pid) {
                socket_close($fd[0]);
                $return = $this->read($fd[1]);
                if ($return instanceof Error) {
                    throw new \Exception($return->getMessage());
                }
                pcntl_waitpid($pid, $status);

                return $return;
            } else {
                socket_close($fd[1]);
                $this->initializeChildProcess($fd);
                $this->callUserFunc($callback, $fd);
                exit(0);
            }
        } else {
            $this->spawnWorker();

            if ($this->workerPid !== getmypid()) {
                $closure = new SerializableClosure($callback);
                $this->write($this->fd[1], $closure);
                $return = $this->readFromWorker($this->fd[1]);

                if ($return instanceof Error) {
                    throw new \Exception($return->getMessage());
                }

                return $return;
            }
        }
    }

    protected function write($socket, $msg)
    {
        $serializedMsg = serialize($msg);
        $serializedMsgLength = strlen($serializedMsg);
        socket_write($socket, pack('L', $serializedMsgLength));
        socket_write($socket, $serializedMsg);
    }

    protected function read($socket)
    {
        socket_set_block($socket);
        $data = unpack('L', socket_read($socket, 4));
        $serializedMsgLength = $data[1];
        $serializedMsg = '';
        socket_recv($socket, $serializedMsg, $serializedMsgLength, MSG_WAITALL);
        $msg = unserialize($serializedMsg);

        return $msg;
    }

    protected function readFromWorker($socket)
    {
        $return = null;

        do {
            socket_set_nonblock($socket);
            $data = socket_read($socket, 4);
            if ($data !== false) {
                $returnMsgLength = unpack('L', $data)[1];
                socket_set_block($socket);
                $returnMsg = '';
                socket_recv($socket, $returnMsg, $returnMsgLength, MSG_WAITALL);

                $return = unserialize($returnMsg);
            }

            $pid = pcntl_waitpid($this->workerPid, $status, WNOHANG);
            if ($pid == $this->workerPid) {
                if (pcntl_wifexited($status)) {
                    $code = pcntl_wexitstatus($status);
                    throw new \Exception("Worker process has exited abnormally (code $code)");
                }
            }

            // Sleep for 100µs
            usleep(100);
        } while ($data === false && socket_last_error() === SOCKET_EWOULDBLOCK);

        return $return;
    }

    protected function spawnWorker()
    {
        if (!isset($this->workerPid)) {
            $this->fd = $this->createSocketPair();

            $pid = $this->fork();
            if ($pid) {
                $this->workerPid = $pid;
            } else {
                $this->workerPid = getmypid();

                $this->initializeChildProcess($this->fd);

                while (1) {
                    $callback = $this->read($this->fd[0]);

                    $this->callUserFunc($callback, $this->fd);
                }
            }
        } elseif ($this->workerPid === getmypid()) {
            throw new \Exception('Worker cannot use OmekaSandbox');
        }
    }

    protected function createSocketPair()
    {
        $fd = [];
        if (false === socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd)) {
            throw new \Exception('Failed to create sockets pair');
        }

        return $fd;
    }

    protected function fork()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception('Unable to fork');
        }

        return $pid;
    }

    protected function initializeChildProcess($fd)
    {
        $omekaPath = $this->getContext()->getOmekaPath() ?: '';
        cli_set_process_title("omeka-cli worker [$omekaPath]");

        // PHPUnit error handler can be confused in child process
        restore_error_handler();

        // Disable output buffering
        while (@ob_end_clean());

        register_shutdown_function(function () use ($fd) {
            $error = error_get_last();
            if ($error && $error['type'] === E_ERROR) {
                $message = sprintf('%s at %s line %s', $error['message'], $error['file'], $error['line']);
                $return = new Error($message);
                $this->write($fd[0], $return);
            }
        });

        $this->initializeOmeka();
    }

    protected function callUserFunc($callback, $fd)
    {
        $e = null;
        try {
            $return = call_user_func($callback);
            $this->write($fd[0], $return);
        } catch (\Throwable $_e) {
            $e = $_e;
        } catch (\Exception $_e) {
            $e = $_e;
        }

        if (isset($e)) {
            $message = sprintf('%s at %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
            $return = new Error($message);
            $this->write($fd[0], $return);
            exit(1);
        }
    }

    protected function initializeOmeka()
    {
        $omekaPath = $this->getContext()->getOmekaPath();

        if ($omekaPath && is_readable($omekaPath . '/bootstrap.php')) {
            require_once $omekaPath . '/bootstrap.php';

            if (class_exists('Omeka_Application')) {
                $application = new \Omeka_Application(APPLICATION_ENV);
                $bootstrap = $application->getBootstrap();
                $bootstrap->setOptions([
                    'resources' => [
                        'theme' => [
                            'basePath' => THEME_DIR,
                            'webBasePath' => WEB_THEME,
                        ],
                    ],
                ]);

                if (APPLICATION_ENV === 'testing') {
                    \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
                }

                $bootstrap->getPluginResource('Options')->setInstallerRedirect(false);
                try {
                    $bootstrap->bootstrap('Db');
                    $bootstrap->bootstrap('Options');

                    $db = $bootstrap->getResource('Db');
                    $superUsers = $db->getTable('User')->findBy([
                        'role' => 'super',
                        'active' => '1',
                    ]);
                    if (!empty($superUsers)) {
                        $bootstrap->getContainer()->currentuser = $superUsers[0];
                    }

                    $application->initialize();
                } catch (\Exception $e) {
                    // Do nothing
                }
            }
        }
    }
}
