<?php

namespace OmekaCli\Command\Plugin;

class UninstallCommand extends AbstractPluginCommand
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
            $this->getSandbox()->execute(function () use ($pluginName) {
                $pluginLoader = \Zend_Registry::get('plugin_loader');
                $plugin = $pluginLoader->getPlugin($pluginName);
                if (!$plugin) {
                    throw new \Exception("$pluginName is not installed");
                }

                $pluginBroker = \Zend_Registry::get('pluginbroker');
                $pluginInstaller = new \Omeka_Plugin_Installer($pluginBroker, $pluginLoader);

                $pluginInstaller->uninstall($plugin);
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->info('{plugin} uninstalled', array('plugin' => $pluginName));

        return 0;
    }
}
