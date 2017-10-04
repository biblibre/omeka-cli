<?php

namespace OmekaCli\Command;

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

    public function run($options, $args)
    {
        $commands = $this->commandManager;
        foreach ($commands->getCommandsNames() as $name) {
            $command = $commands->getCommand($name);
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
