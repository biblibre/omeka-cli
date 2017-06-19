<?php

namespace OmekaCli\Command;

use GetOptionKit\OptionCollection;

abstract class AbstractCommand implements CommandInterface
{
    public function getOptionsSpec()
    {
        return new OptionCollection;
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
