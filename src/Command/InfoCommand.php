<?php

namespace OmekaCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('info');
        $this->setDescription('print informations about the Omeka installation');
        $this->setHelp('This command shows several informations like Omeka base directory, Omeka version, database version and installed themes and plugins');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            return 0;
        }

        $omeka = $this->getOmeka();

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

        $output->writeln('Omeka base directory: ' . $omeka->BASE_DIR);
        $output->writeln('Omeka version:        ' . $version);
        $output->writeln('Database version:     ' . $dbVersion);

        if (0 !== version_compare($version, $dbVersion)) {
            $output->writeln('<comment>Warning: Omeka version and database version are not the same!</comment>');
        }

        $output->writeln('Admin theme:          ' . $omeka->get_option('admin_theme'));
        $output->writeln('Public theme:         ' . $omeka->get_option('public_theme'));
        $output->writeln('Plugins (actives):');
        foreach ($activePlugins as $plugin) {
            $output->writeln("\t" . sprintf('%s - %s', $plugin['name'], $plugin['version']));
        }
        $output->writeln('Plugins (inactives):');
        foreach ($inactivePlugins as $plugin) {
            $output->writeln("\t" . sprintf('%s - %s', $plugin['name'], $plugin['version']));
        }

        return 0;
    }
}
