<?php

namespace OmekaCli\Command\Plugin;

use OmekaCli\Plugin\Updater;
use OmekaCli\Sandbox\OmekaSandbox;

class UpdateCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'update plugins';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-update <plugin-name>\n";
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

        try {
            $iniVersion = $this->getSandbox()->execute(function () use ($pluginName) {
                $pluginLoader = \Zend_Registry::get('plugin_loader');
                $plugin = $pluginLoader->getPlugin($pluginName);
                if (!isset($plugin)) {
                    throw new \Exception("Plugin $pluginName not found");
                }

                return $plugin->getIniVersion();
            });

            $updater = new Updater();
            $updater->setLogger($this->logger);
            $updater->setContext($this->getContext());

            $latestVersion = $updater->getPluginLatestVersion($pluginName);
            if (version_compare($latestVersion, $iniVersion) <= 0) {
                $this->logger->error('{plugin} is up-to-date ({version})', array('plugin' => $pluginName, 'version' => $iniVersion));

                return 1;
            }

            $this->logger->info('Updating {plugin}', array('plugin' => $pluginName));
            if (!$updater->update($pluginName)) {
                $this->logger->error('Plugin update failed');

                return 1;
            }

            $sandbox = new OmekaSandbox();
            $sandbox->setContext($this->getContext());
            $sandbox->execute(function () use ($pluginName) {
                $pluginLoader = \Zend_Registry::get('plugin_loader');
                $pluginInstaller = new \Omeka_Plugin_Installer(
                    \Zend_Registry::get('pluginbroker'),
                    $pluginLoader
                );

                $plugin = $pluginLoader->getPlugin($pluginName);

                $iniReader = new \Omeka_Plugin_Ini(PLUGIN_DIR);
                $iniReader->load($plugin);

                $iniVersion = $plugin->getIniVersion();
                $pluginInstaller->upgrade($plugin);
                $this->logger->info('Plugin {plugin} upgraded successfully to {version}', array('plugin' => $pluginName, 'version' => $iniVersion));
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
