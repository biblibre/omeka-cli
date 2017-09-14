<?php

namespace OmekaCli\Command\Plugin;

use Plugin;
use OmekaCli\Application;

class InstallCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'install a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-install PLUGIN_NAME\n"
             . "\tplin PLUGIN_NAME\n";
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
        if ($plugin) {
            $this->logger->error('{plugin} is already installed', array('plugin' => $plugin->name));

            return 1;
        }

        $plugin = new Plugin();
        $plugin->name = $pluginName;

        $this->getPluginIniReader()->load($plugin);

        $missingDeps = $this->getMissingDependencies($plugin);
        if (!empty($missingDeps)) {
            $this->logger->error('Some required plugins are missing: ' . implode(', ', $missingDeps));

            return 1;
        }

        $this->getPluginInstaller()->install($plugin);

        $plugin = get_db()->getTable('Plugin')->findByDirectoryName($plugin->name);
        if ($plugin && $plugin->isActive()) {
            $this->logger->info('{plugin} installed', array('plugin' => $plugin->name));
        } else {
            $this->logger->error('Installation of {plugin} failed', array('plugin' => $plugin->name));
        }

        return 0;
    }
}
