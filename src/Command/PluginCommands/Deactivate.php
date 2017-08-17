<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;

class Deactivate extends AbstractCommand
{
    public function getDescription()
    {
        return 'deactivate a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-deactivate PLUGIN_NAME' . PHP_EOL
             . '    plde PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Deactivate a plugin' . PHP_EOL;
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
        if (!$plugin->isActive()) {
            $this->logger->error('plugin already deactivated');
            return 1;
        }

        $this->logger->info('Deactivating plugin');
        PUtils::getInstaller($plugin)->deactivate($plugin);
        $this->logger->info('Plugin deactivated');

        return 0;
    }
}
