<?php

namespace OmekaCli\Command;

use OmekaCli\Omeka\PluginInstaller;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginListCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('plugin-list');
        $this->setDescription('List locally available plugins');
        $this->addOption('state', null, InputOption::VALUE_REQUIRED, 'Filter plugins by state', 'active,inactive,uninstalled,version-mismatch');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $states = explode(',', $input->getOption('state'));

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext($this->getContext());
        $plugins = $pluginInstaller->getAll();

        $plugins = array_filter($plugins, function ($plugin) use ($states) {
            $state = $this->getPluginState($plugin);
            if (in_array($state, $states)) {
                return true;
            }

            return false;
        });

        // Sort by state (active, inactive, uninstalled) and by name
        uasort($plugins, function ($a, $b) {
            if (isset($a['id']) !== isset($b['id'])) {
                return (int) $b['id'] - (int) $a['id'];
            }
            if ($a['active'] != $b['active']) {
                return (int) $b['active'] - (int) $a['active'];
            }

            return strcmp($a['name'], $b['name']);
        });

        $table = new Table($output);
        $table->setHeaders(['Name', 'Version', 'State']);
        foreach ($plugins as $plugin) {
            $name = $plugin['name'];
            $iniVersion = $plugin['info']['version'];
            $state = $this->getPluginState($plugin);
            if ($state === 'version-mismatch') {
                $state .= sprintf(' (db version: %s)', $plugin['version']);
            }
            $table->addRow([$name, $iniVersion, $state]);
        }
        $table->render();
    }

    protected function getPluginState(array $plugin)
    {
        if ($plugin['version'] && version_compare($plugin['version'], $plugin['info']['version'])) {
            $state = 'version-mismatch';
        } elseif ($plugin['active']) {
            $state = 'active';
        } elseif ($plugin['id']) {
            $state = 'inactive';
        } else {
            $state = 'uninstalled';
        }

        return $state;
    }
}
