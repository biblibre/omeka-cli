<?php

namespace OmekaCli\Command\Plugin;

use OmekaCli\Command\AbstractCommand;
use OmekaCli\Omeka\PluginInstaller;

class DisableCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'disable a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "    plugin-disable <plugin>\n"
             . "    pldis <plugin>\n";
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
            $pluginInstaller->disable($pluginName);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return 1;
        }

        $this->logger->notice('{plugin} disabled', array('plugin' => $pluginName));

        return 0;
    }
}
