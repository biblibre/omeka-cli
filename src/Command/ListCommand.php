<?php

namespace OmekaCli\Command;

use OmekaCli\Commands;

class ListCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'list available commands';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\tlist\n";

        return $usage;
    }

    public function run($options, $args, $application)
    {
        $commands = $application->getCommandManager();
        foreach ($commands->getAll() as $name => $command) {
            $aliases = $commands->getCommandAliases($name);
            $description = $command->getDescription();

            print "$name";
            if (isset($description)) {
                print " -- $description";
            }
            if (!empty($aliases)) {
                print " (aliases: " . implode(', ', $aliases) . ")";
            }
            print "\n";
        }
    }
}
