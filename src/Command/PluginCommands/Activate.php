<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;

use Omeka\Plugin;
use Omeka\Plugin\Broker;
use Omeka\Plugin\Installer;

class Activate extends AbstractCommand
{
    public function getDescription()
    {
        return 'activate a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-activate PLUGIN_NAME' . PHP_EOL
             . '    plac PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Activate a plugin' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) == 1) {
            $plugin = PUtils::getPlugin(array_pop($args));
        } else {
            $this->logger->error($this->getUsage());
            return 1;
        }

        $missingDeps = PUtils::getMissingDependencies($plugin);
        if (!empty($missingDeps)) {
            $this->logger->error('missing plugins ' . implode(',', $missingDeps));
            return 1;
        }

        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->activate($plugin);

        $this->logger->info('Plugin activated');

        return 0;
    }
}
