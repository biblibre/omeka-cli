<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class UpgradeCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'upgrade omeka-cli or Omeka';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '\tupgrade' . PHP_EOL
               . PHP_EOL
               . 'Upgrade omeka-cli or Omeka.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        echo 'It works!' . PHP_EOL;

        return 0;
    }
}
