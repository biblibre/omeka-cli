<?php

namespace OmekaCli\Command;

class HelpCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'print help for a specific command';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\thelp COMMAND\n";

        return $usage;
    }

    public function run($options, $args)
    {
        if (empty($args)) {
            echo $this->getUsage();

            return 0;
        }

        $commandName = reset($args);
        $commands = $this->commandManager;
        $command = $this->commandManager->getCommand($commandName);
        if (!isset($command)) {
            $this->logger->error('Command {name} does not exist', array(
                'name' => $commandName,
            ));

            return 1;
        }

        $description = $command->getDescription();
        $usage = $command->getUsage();
        if (!$description && !$usage) {
            $this->logger->error('There is no available help for this command');

            return 1;
        }

        if ($description) {
            echo sprintf('%s - %s', $commandName, $description);
        } else {
            echo $commandName;
        }
        echo "\n";

        echo "\n";
        echo $usage;
        echo "\n";
    }
}
