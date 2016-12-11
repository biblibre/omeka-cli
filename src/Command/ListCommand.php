<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

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

    public function run($options, $args, Application $application)
    {
        $commands = $application->getCommandManager();
        foreach ($commands->getAll() as $name => $command) {
            $aliases = $commands->getCommandAliases($name);
            $description = $command->getDescription();

            echo "$name";
            if (isset($description)) {
                echo " -- $description";
            }
            if (!empty($aliases)) {
                echo ' (aliases: ' . implode(', ', $aliases) . ')';
            }
            echo "\n";
        }
    }
}
