<?php

namespace OmekaCli\Command\PluginCommands;

use Zend_Registry;
use OmekaCli\Application;

class Deactivate extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'deactivate a plugin';
    }

    public function getUsage()
    {
        return 'Usage:' . PHP_EOL
             . '    plugin-deactivate PLUGIN_NAME' . PHP_EOL
             . '    plde PLUGIN_NAME' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $pluginName = reset($args);
        $plugin = $this->getPlugin($pluginName);
        if (!$plugin) {
            $this->logger->error('plugin not found');

            return 1;
        }

        if (!$plugin->isActive()) {
            $this->logger->error('plugin is already inactive');

            return 1;
        }

        $this->getPluginInstaller()->deactivate($plugin);

        $this->logger->info('{plugin} deactivated', array('plugin' => $plugin->name));

        return 0;
    }
}
