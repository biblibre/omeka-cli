<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;

class Uninstall extends AbstractCommand
{
    public function getDescription()
    {
        return 'uninstall a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-uninstall PLUGIN_NAME' . PHP_EOL
             . '    plun PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Uninstall a plugin' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) == 1) {
            $pluginName = array_shift($args);
        } else {
            $this->logger->error($this->getUsage());

            return 1;
        }

        $this->logger->info('Checking Omeka status');
        if (!$application->isOmekaInitialized()) {
            $this->logger->error('omeka not initialized here');

            return 1;
        }

        $this->logger->info('Retrieving plugin');
        $plugin = PUtils::getPlugin($pluginName);
        if (!$plugin) {
            $this->logger->error('plugin not installed');

            return 1;
        }

        $this->logger->info('Uninstalling plugin');
        $installer = PUtils::getInstaller($plugin);
        $installer->uninstall($plugin);

        $this->logger->info('Uninstallation successful');

        return 0;
    }
}
