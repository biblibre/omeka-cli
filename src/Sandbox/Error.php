<?php

namespace OmekaCli\Sandbox;

class Error
{
    protected $message;

    public function __construct($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
