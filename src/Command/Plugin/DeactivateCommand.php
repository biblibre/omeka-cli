<?php

namespace OmekaCli\Command\Plugin;

class DeactivateCommand extends AbstractPluginCommand
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
                    throw new \Exception('Plugin not found');
                }

                if (!$plugin->isActive()) {
                    throw new \Exception('Plugin is already inactive');
                }

                $pluginBroker = \Zend_Registry::get('pluginbroker');
                $pluginInstaller = new \Omeka_Plugin_Installer(
                    $pluginBroker,
                    $pluginLoader
                );
                $pluginInstaller->deactivate($plugin);
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->notice('{plugin} deactivated', array('plugin' => $pluginName));

        return 0;
    }
}
