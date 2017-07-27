<?php

class FakeLogger extends \OmekaCli\Logger
{
    protected $output = '';

    public function log($level, $message, array $context = array())
    {
        if (!$this->isLogLevelKnown($level)) {
            throw new InvalidArgumentException("level $level is invalid");
        }

        $message = $this->getMessage($level, $message, $context);
        $this->output .= $message;
    }

    public function getOutput()
    {
        return $this->output;
    }
}
