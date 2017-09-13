<?php

namespace OmekaCli;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

class Logger extends AbstractLogger
{
    const DEFAULT_VERBOSITY = 6;

    protected static $logLevels = array(
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
        LogLevel::NOTICE,
        LogLevel::INFO,
        LogLevel::DEBUG,
    );

    protected $verbosity;

    public function __construct()
    {
        $this->verbosity = self::DEFAULT_VERBOSITY;
    }

    public function log($level, $message, array $context = array())
    {
        if (!$this->isLogLevelKnown($level)) {
            throw new InvalidArgumentException("level $level is invalid");
        }

        if ($this->shouldLog($level)) {
            $message = $this->getMessage($level, $message, $context);
            fwrite(STDERR, $message . PHP_EOL);
        }
    }

    public function setVerbosity($verbosity)
    {
        $this->verbosity = $verbosity;
    }

    protected function getMessage($level, $message, $context)
    {
        $message = $this->interpolate($message, $context);
        if ($level == LogLevel::INFO) {
            $msg = sprintf('%s', $message);
        } else {
            $msg = sprintf('%s: %s', ucfirst($level), $message);
        }

        return $msg;
    }

    protected function isLogLevelKnown($level)
    {
        $known = false;

        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
            case LogLevel::WARNING:
            case LogLevel::NOTICE:
            case LogLevel::INFO:
            case LogLevel::DEBUG:
                $known = true;
        }

        return $known;
    }

    protected function interpolate($message, array $context = array())
    {
        $replace = array();
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }

        return strtr($message, $replace);
    }

    protected function shouldLog($level)
    {
        return array_search($level, self::$logLevels) < $this->verbosity;
    }
}
