<?php

namespace OmekaCli\Command\Plugin;

use Zend_Registry;
use Plugin;
use Omeka_Plugin_Installer;
use OmekaCli\Command\AbstractCommand;

abstract class AbstractPluginCommand extends AbstractCommand
{
    protected function getPlugin($name)
    {
        return $this->getPluginLoader()->getPlugin($name);
    }

    protected function getPluginLoader()
    {
        return Zend_Registry::get('plugin_loader');
    }

    protected function getPluginBroker()
    {
        return Zend_Registry::get('pluginbroker');
    }

    protected function getPluginIniReader()
    {
        return Zend_Registry::get('plugin_ini_reader');
    }

    protected function getPluginInstaller()
    {
        $pluginBroker = $this->getPluginBroker();
        $pluginLoader = $this->getPluginLoader();

        return new Omeka_Plugin_Installer($pluginBroker, $pluginLoader);
    }

    protected function getMissingDependencies(Plugin $plugin)
    {
        $requiredPlugins = $plugin->getRequiredPlugins();
        $missingDeps = array_filter($requiredPlugins, function ($requiredPlugin) {
            return !plugin_is_active($requiredPlugin);
        });

        return $missingDeps;
    }
}
