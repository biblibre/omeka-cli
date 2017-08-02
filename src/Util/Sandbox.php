<?php

namespace OmekaCli\Util;

class Sandbox
{
    public function run(callable $callback)
    {
        if (function_exists('get_db') ) {
            try {
                get_db()->closeConnection();
            } catch (\Exception $e) {
                //
            }
        }

        $fd = array();
        if (false === socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $fd)) {
            throw new \Exception('Failed to create sockets pair');
        }

        $pid = pcntl_fork();
        if ($pid == -1) {
            throw new \Exception('Unable to fork');
        }

        if ($pid) {
            socket_close($fd[0]);

            $message = '';
            do {
                $msg = socket_read($fd[1], 1024);
                if (!empty($msg)) {
                    $message .= $msg;
                }
            } while ($msg !== false && $msg !== '');

            socket_close($fd[1]);

            pcntl_wait($status);
            if (pcntl_wifexited($status)) {
                $exitCode = pcntl_wexitstatus($status);

                return array(
                    'exit_code' => $exitCode,
                    'message' => $message,
                );
            }
        } else {
            fclose(STDOUT);
            fclose(STDERR);
            socket_close($fd[1]);

            register_shutdown_function(function () use ($fd) {
                $error = error_get_last();
                if ($error['type'] != E_USER_NOTICE) {
                    socket_write($fd[0], $error['message']);
                }
                socket_close($fd[0]);
            });

            // Prevent PHPUnit from stopping execution at trigger_error
            $oldReportingLevel = error_reporting();
            error_reporting($oldReportingLevel ^ E_USER_NOTICE);

            trigger_error('');

            error_reporting($oldReportingLevel);

            exit(call_user_func($callback, $fd[0]));
        }
    }
}
