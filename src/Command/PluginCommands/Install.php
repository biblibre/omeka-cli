<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;

class Install extends AbstractCommand
{
    public function getDescription()
    {
        return 'install a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-install PLUGIN_NAME' . PHP_EOL
             . '    plin PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Install a plugin' . PHP_EOL;
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

        $this->logger->info('Checking plugin directory');
        if (!file_exists(PLUGIN_DIR . '/' . $pluginName)) {
            $this->logger->error('plugin not found');

            return 1;
        }

        $this->logger->info('Checking dependencies');
        $missingDeps = PUtils::getMissingDependencies($pluginName);
        if (!empty($missingDeps)) {
            $this->logger->error('error: missing plugins ' . implode(',', $missingDeps));

            return 1;
        }

        $this->logger->info('Creating new plugin');
        $ini = parse_ini_file(PLUGIN_DIR . '/' . $pluginName . '/plugin.ini');
        $version = $ini['version'];

        $plugin = new \Plugin();
        $plugin->name = $pluginName;
        $plugin->setIniVersion($version);
        $plugin->setLoaded(true);

        $this->logger->info('Installing plugin');
        $installer = PUtils::getInstaller($plugin);
        $installer->install($plugin);

        $results = get_db()->getTable('Plugin')->findBy(array(
            'active' => 1,
            'name' => $plugin->name,
        ));
        if (!empty($results) && array_shift($results)->isActive()) {
            $this->logger->info('Installation succeeded');
        } else {
            $this->logger->error('installation failed');
        }

        return 0;
    }
}
