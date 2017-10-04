<?php

namespace OmekaCli\Command;

class VersionCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'print version of omeka-cli';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\tversion\n"
            . "\n"
            . "Print version of omeka-cli\n";

        return $usage;
    }

    public function run($options, $args)
    {
        echo OMEKACLI_VERSION . "\n";
    }
}
