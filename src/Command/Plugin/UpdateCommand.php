<?php

namespace OmekaCli\Command\Plugin;

use Omeka_Plugin_Ini;
use Omeka_Plugin_Installer_Exception;
use OmekaCli\Application;
use OmekaCli\Plugin\Updater;

class UpdateCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'update plugins';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-update [<plugin_name>]\n"
             . "\n"
             . "If <plugin_name> is given, update only this plugin.\n";
    }

    public function run($options, $args, Application $application)
    {
        if (!$application->isOmekaInitialized()) {
            $this->logger->error('Omeka not initialized here.');

            return 1;
        }

        if (count($args) > 1) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $pluginName = reset($args);

        if (!empty($pluginName)) {
            $plugin = $this->getPlugin($pluginName);
            if (!isset($plugin)) {
                $this->logger->error('Plugin {plugin} not found', array('plugin' => $pluginName));

                return 1;
            }

            $plugins = array($plugin);
        } else {
            $plugins = $this->getPluginLoader()->getPlugins();
        }

        $pluginInstaller = $this->getPluginInstaller();
        $updater = new Updater();
        $updater->setLogger($this->logger);

        foreach ($plugins as $plugin) {
            $latestVersion = $updater->getPluginLatestVersion($plugin);
            if (version_compare($latestVersion, $plugin->getIniVersion()) <= 0) {
                $this->logger->notice('{plugin} is up-to-date ({version})', array('plugin' => $plugin->name, 'version' => $plugin->getIniVersion()));
                continue;
            }

            // If a plugin is active, its code is already loaded at this point
            // and will not be reloaded after the update, so the upgrade hook
            // will not be up-to-date. We have to force the user to manually
            // deactivate the plugin first.
            // TODO Find another way
            if ($plugin->isActive()) {
                $this->logger->error('{plugin} is active and needs to be deactivated before being updated', array('plugin' => $plugin->name));
                continue;
            }

            $this->logger->info('Updating {plugin}', array('plugin' => $plugin->name));
            if (!$updater->update($plugin)) {
                continue;
            }

            $iniReader = new Omeka_Plugin_Ini(PLUGIN_DIR);
            $iniReader->load($plugin);

            try {
                $iniVersion = $plugin->getIniVersion();
                $pluginInstaller->upgrade($plugin);
                $this->logger->info('Plugin {plugin} upgraded successfully to {version}', array('plugin' => $plugin->name, 'version' => $iniVersion));
            } catch (Omeka_Plugin_Installer_Exception $e) {
                $this->logger->error('Failed to upgrade {plugin}: {message}', array('plugin' => $plugin->name, 'message' => $e->getMessage()));
            }
        }

        return 0;
    }
}
