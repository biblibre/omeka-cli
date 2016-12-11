<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class HelpCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'print help for a specific command';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\thelp COMMAND\n"
            . "\n"
            . "Print help for a specific command\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            echo $this->getUsage();

            return 0;
        }

        $logger = $application->getLogger();

        $commandName = reset($args);
        $command = $application->getCommandManager()->get($commandName);
        if (!isset($command)) {
            $logger->error('Command {name} does not exist', array(
                'name' => $commandName,
            ));

            return 1;
        }

        $usage = $command->getUsage();
        if (!$usage) {
            echo "There is no available help for this command\n";
        }

        echo $usage;
        echo "\n";
    }
}
