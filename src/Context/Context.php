<?php

namespace OmekaCli\Context;

class Context
{
    protected $omekaPath;

    public function __construct($omekaPath = null)
    {
        $this->omekaPath = $omekaPath;
    }

    public function getOmekaPath()
    {
        return $this->omekaPath;
    }
}
