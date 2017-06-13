<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class OptionsCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'edit the "omeka_options" table';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
               . "\toption\n"
               . "\n"
               . "Edit the \"omeka_options\" table.\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        echo "This command does nothing for now.\n";
    }
}
