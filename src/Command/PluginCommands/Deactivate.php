<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;

use Omeka\Plugin;
use Omeka\Plugin\Broker;
use Omeka\Plugin\Installer;

class Deactivate extends AbstractCommand
{
    public function getDescription()
    {
        return 'deactivate a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-deactivate PLUGIN_NAME' . PHP_EOL
             . '    plac PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Deactivate a plugin' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) == 1) {
            $pluginName = array_pop($args);
        } else {
            $this->logger->error($this->getUsage());
            return 1;
        }

        $plugins = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));
        if (empty($plugins)) {
            $this->logger->error('plugin not installed');
            return 1;
        }
        $plugin = array_pop($plugins);

        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->deactivate($plugin);

        $this->logger->info('Plugin deactivated');

        return 0;
    }
}
