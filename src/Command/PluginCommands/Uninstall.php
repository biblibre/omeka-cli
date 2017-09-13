<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;

class Uninstall extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'uninstall a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-uninstall PLUGIN_NAME\n"
             . "\tplun PLUGIN_NAME\n";
    }

    public function run($options, $args, Application $application)
    {
        if (!$application->isOmekaInitialized()) {
            $this->logger->error('omeka not initialized here');

            return 1;
        }

        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $pluginName = reset($args);

        $plugin = $this->getPlugin($pluginName);
        if (!$plugin) {
            $this->logger->error('{plugin} is not installed', array('plugin' => $plugin->name));

            return 1;
        }

        $this->getPluginInstaller()->uninstall($plugin);

        $this->logger->info('{plugin} uninstalled', array('plugin' => $plugin->name));

        return 0;
    }
}
