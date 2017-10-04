<?php

namespace OmekaCli\Sandbox;

use SuperClosure\SerializableClosure;
use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;

class OmekaSandbox implements ContextAwareInterface
{
    use ContextAwareTrait;

    protected $fd = array();
    protected $workerPid;

    public function __destruct()
    {
        if ($this->workerPid && getmypid() !== $this->workerPid) {
            if (0 === pcntl_waitpid($this->workerPid, $status, WNOHANG)) {
                // Tell the child to exit
                $this->write($this->fd[1], new SerializableClosure(function () {
                    exit(0);
                }));
                pcntl_waitpid($this->workerPid, $status);
            }
        }

        foreach ($this->fd as $socket) {
            if (is_resource($socket)) {
                socket_close($socket);
            }
        }
    }

    public function execute(callable $callback, ...$args)
    {
        if (!isset($this->workerPid)) {
            $fd = array();
            if (false === socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd)) {
                throw new \Exception('Failed to create sockets pair');
            }
            $this->fd = $fd;

            $pid = pcntl_fork();
            if ($pid == -1) {
                throw new \Exception('Unable to fork');
            }

            if ($pid) {
                $this->workerPid = $pid;
            } else {
                $this->workerPid = getmypid();

                //fclose(STDOUT);
                //fclose(STDERR);

                $omekaPath = $this->getContext()->getOmekaPath() ?: '';
                cli_set_process_title("omeka-cli worker [$omekaPath]");

                // PHPUnit error handler can be confused in child process
                restore_error_handler();

                // Disable output buffering
                while (@ob_end_clean());

                $this->initializeOmeka();

                register_shutdown_function(function () {
                    $error = error_get_last();
                    if ($error && $error['type'] === E_ERROR) {
                        $message = sprintf('%s at %s line %s', $error['message'], $error['file'], $error['line']);
                        $return = new Error($message);
                        $this->write($this->fd[0], $return);
                    }
                });

                while (1) {
                    $callback = $this->read($this->fd[0]);

                    try {
                        $return = call_user_func_array($callback, $args);
                        $this->write($this->fd[0], $return);
                    } catch (\Throwable $e) {
                        $message = sprintf('%s at %s line %s', $e->getMessage(), $e->getFile(), $e->getLine());
                        $return = new Error($message);
                        $this->write($this->fd[0], $return);
                        exit(1);
                    }
                }
            }
        } elseif ($this->workerPid === getmypid()) {
            throw new \Exception('Worker cannot use OmekaSandbox');
        }

        if ($this->workerPid !== getmypid()) {
            $closure = new SerializableClosure($callback);
            $this->write($this->fd[1], $closure);
            $return = $this->read($this->fd[1]);

            if ($return instanceof Error) {
                throw new \Exception($return->getMessage());
            }

            return $return;
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
        $return = null;

        do {
            socket_set_nonblock($socket);
            $data = socket_read($socket, 4);
            if ($data !== false) {
                $returnMsgLength = unpack('L', $data)[1];
                socket_set_block($socket);
                $returnMsg = socket_read($socket, $returnMsgLength);

                $return = unserialize($returnMsg);
            }

            // Check if child is still there
            if (getmypid() != $this->workerPid) {
                $pid = pcntl_waitpid($this->workerPid, $status, WNOHANG);
                if ($pid == $this->workerPid) {
                    if (pcntl_wifexited($status)) {
                        $code = pcntl_wexitstatus($status);
                        throw new \Exception("Worker process has exited abnormally (code $code)");
                    }
                }
            }

            // Sleep for 100µs
            usleep(100);
        } while ($data === false && socket_last_error() === SOCKET_EWOULDBLOCK);

        return $return;
    }

    protected function initializeOmeka()
    {
        $omekaPath = $this->getContext()->getOmekaPath();

        if ($omekaPath && is_readable($omekaPath . '/bootstrap.php')) {
            require_once $omekaPath . '/bootstrap.php';

            if (class_exists('Omeka_Application')) {
                $application = new \Omeka_Application(APPLICATION_ENV);
                $bootstrap = $application->getBootstrap();
                $bootstrap->setOptions(array(
                    'resources' => array(
                        'theme' => array(
                            'basePath' => THEME_DIR,
                            'webBasePath' => WEB_THEME,
                        ),
                    ),
                ));

                if (APPLICATION_ENV === 'testing') {
                    \Zend_Controller_Front::getInstance()->getRouter()->addDefaultRoutes();
                }

                $bootstrap->getPluginResource('Options')->setInstallerRedirect(false);
                try {
                    $bootstrap->bootstrap('Db');
                    $bootstrap->bootstrap('Options');

                    $db = $bootstrap->getResource('Db');
                    $superUsers = $db->getTable('User')->findBy(array(
                        'role' => 'super',
                        'active' => '1',
                    ));
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
