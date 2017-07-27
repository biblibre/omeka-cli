<?php

namespace OmekaCli\Command;

use GetOptionKit\OptionCollection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

abstract class AbstractCommand implements CommandInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

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
