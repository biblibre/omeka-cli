<?php

namespace OmekaCli\Command\Plugin;

use OmekaCli\Command\AbstractCommand;
use OmekaCli\Omeka\PluginInstaller;

class EnableCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'enable a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-enable PLUGIN_NAME\n"
             . "\tplen PLUGIN_NAME\n";
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

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext($this->getContext());

        try {
            $pluginInstaller->enable($pluginName);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->notice('{plugin} enabled', array('plugin' => $pluginName));

        return 0;
    }
}
