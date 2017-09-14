<?php

namespace OmekaCli\Command\Plugin;

use OmekaCli\Application;

class ActivateCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'activate a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-activate PLUGIN_NAME\n"
             . "\tplac PLUGIN_NAME\n";
    }

    public function run($options, $args, Application $application)
    {
        if (!$application->isOmekaInitialized()) {
            $this->logger->error('Omeka is not initialized here');

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
            $this->logger->error('plugin not found');

            return 1;
        }

        if ($plugin->isActive()) {
            $this->logger->error('plugin is already active');

            return 1;
        }

        $missingDeps = $this->getMissingDependencies($plugin);
        if (!empty($missingDeps)) {
            $this->logger->error('missing plugins ' . implode(',', $missingDeps));

            return 1;
        }

        $this->getPluginInstaller()->activate($plugin);

        $this->logger->info('{plugin} activated', array('plugin' => $plugin->name));

        return 0;
    }
}
