<?php

namespace OmekaCli\Command\Plugin;

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

                if ($plugin->isActive()) {
                    throw new \Exception('Plugin is already active');
                }

                $requiredPlugins = $plugin->getRequiredPlugins();
                $missingDeps = array_filter($requiredPlugins, function ($name) {
                    return !plugin_is_active($name);
                });
                if (!empty($missingDeps)) {
                    throw new \Exception('Missing plugins ' . implode(', ', $missingDeps));
                }

                $pluginBroker = \Zend_Registry::get('pluginbroker');
                $pluginInstaller = new \Omeka_Plugin_Installer($pluginBroker, $pluginLoader);
                $pluginInstaller->activate($plugin);
            });
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->notice('{plugin} activated', array('plugin' => $pluginName));

        return 0;
    }
}
