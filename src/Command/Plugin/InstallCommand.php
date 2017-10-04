<?php

namespace OmekaCli\Command\Plugin;

use Plugin;
use Zend_Registry;

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

    public function run($options, $args)
    {
        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $this->logger->error('Not in an Omeka directory');

            return 1;
        }

        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $pluginName = reset($args);

        $isInstalled = $this->getSandbox()->execute(function () use ($pluginName) {
            $pluginLoader = Zend_Registry::get('plugin_loader');
            $plugin = $pluginLoader->getPlugin($pluginName);

            return $plugin ? true : false;
        });
        if ($isInstalled) {
            $this->logger->error('{plugin} is already installed', array('plugin' => $pluginName));

            return 1;
        }

        try {
            $this->getSandbox()->execute(function () use ($pluginName) {
                $plugin = new Plugin();
                $plugin->name = $pluginName;

                $pluginIniReader = Zend_Registry::get('plugin_ini_reader');
                $pluginIniReader->load($plugin);

                $requiredPlugins = $plugin->getRequiredPlugins();
                $missingDeps = array_filter($requiredPlugins, function ($requiredPlugin) {
                    return !plugin_is_active($requiredPlugin);
                });
                if (!empty($missingDeps)) {
                    throw new \Exception('Some required plugins are missing: ' . implode(', ', $missingDeps));
                }

                $pluginBroker = Zend_Registry::get('pluginbroker');
                $pluginLoader = Zend_Registry::get('plugin_loader');
                $pluginInstaller = new \Omeka_Plugin_Installer($pluginBroker, $pluginLoader);
                $pluginInstaller->install($plugin);

                $plugin = get_db()->getTable('Plugin')->findByDirectoryName($plugin->name);
                if (!$plugin || !$plugin->isActive()) {
                    throw new \Exception(sprintf('Installation of %s failed', $plugin->name));
                }
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->notice('{plugin} installed', array('plugin' => $pluginName));

        return 0;
    }
}
