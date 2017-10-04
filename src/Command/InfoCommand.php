<?php

namespace OmekaCli\Command;

use OmekaCli\Omeka;

class InfoCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'print informations about the Omeka installation';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    info' . PHP_EOL
               . PHP_EOL
               . 'Print informations about the Omeka installation.' . PHP_EOL
               . 'This command shows :'
               . '- Omeka base directory,' . PHP_EOL
               . '- version Omeka version,' . PHP_EOL
               . '- database verison,' . PHP_EOL
               . '- current admin theme,' . PHP_EOL
               . '- current public theme,' . PHP_EOL
               . '- list of active plugins,' . PHP_EOL
               . '- list of inactive plugins.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args)
    {
        echo 'omeka-cli:            ' . OMEKACLI_VERSION . PHP_EOL;

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            return 0;
        }

        $omeka = new Omeka();
        $omeka->setContext($this->getContext());

        $plugins = $this->getSandbox()->execute(function () {
            $db = get_db();
            $pluginsTable = $db->getTable('Plugin');
            $plugins = array_map(function ($p) {
                return $p->toArray();
            }, $pluginsTable->findAll());

            return $plugins;
        });

        $activePlugins = array_filter($plugins, function ($p) {
            return (bool) $p['active'];
        });
        $inactivePlugins = array_filter($plugins, function ($p) {
            return (bool) !$p['active'];
        });

        $version = $omeka->OMEKA_VERSION;
        $dbVersion = $omeka->get_option('omeka_version');

        echo 'Omeka base directory: ' . $omeka->BASE_DIR . "\n";
        echo 'Omeka version:        ' . $version . "\n";
        echo 'Database version:     ' . $dbVersion . "\n";

        if (0 !== version_compare($version, $dbVersion)) {
            echo "Warning: Omeka version and database version are not the same!\n";
        }

        echo 'Admin theme:          ' . $omeka->get_option('admin_theme') . "\n";
        echo 'Public theme:         ' . $omeka->get_option('public_theme') . "\n";
        echo 'Plugins (actives):' . "\n";
        foreach ($activePlugins as $plugin) {
            echo "\t" . sprintf('%s - %s', $plugin['name'], $plugin['version']) . "\n";
        }
        echo 'Plugins (inactives):' . "\n";
        foreach ($inactivePlugins as $plugin) {
            echo "\t" . sprintf('%s - %s', $plugin['name'], $plugin['version']) . "\n";
        }

        return 0;
    }
}
