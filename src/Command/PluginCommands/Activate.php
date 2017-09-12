<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;

class Activate extends AbstractCommand
{
    public function getDescription()
    {
        return 'activate a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-activate PLUGIN_NAME' . PHP_EOL
             . '    plac PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Activate a plugin' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            $this->logger->error($this->getUsage());

            return 1;
        }

        $this->logger->info('Retrieving plugin');
        $plugin = PUtils::getPlugin(array_pop($args));
        if (!$plugin) {
            $this->logger->error('plugin not found');

            return 1;
        }

        $this->logger->info('Checking plugin status');
        if ($plugin->isActive()) {
            $this->logger->error('plugin already activated');

            return 1;
        }

        $this->logger->info('Checking dependencies');
        $missingDeps = PUtils::getMissingDependencies($plugin->name);
        if (!empty($missingDeps)) {
            $this->logger->error('missing plugins ' . implode(',', $missingDeps));

            return 1;
        }

        $this->logger->info('Activating plugin');
        PUtils::getInstaller($plugin)->activate($plugin);
        $this->logger->info('Plugin activated');

        return 0;
    }
}
