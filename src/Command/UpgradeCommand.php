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
               . '    upgrade' . PHP_EOL
               . PHP_EOL
               . 'Upgrade omeka-cli or Omeka.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (!empty($options) || !empty($args)) {
            echo 'Error: wrong usage.' . PHP_EOL;
            echo $this->getUsage();
            return 1;
        }

        echo 'It works!' . PHP_EOL;

        return 0;
    }
}
