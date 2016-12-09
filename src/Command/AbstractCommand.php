<?php

namespace OmekaCli\Command;

abstract class AbstractCommand implements CommandInterface
{
    public function getOptionsSpec()
    {
        return array();
    }

    public function getDescription()
    {
        return null;
    }

    public function getUsage()
    {
        return null;
    }
}
