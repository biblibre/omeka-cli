<?php

namespace OmekaCli\Command;

use OmekaCli\Omeka\PluginInstaller;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StatusCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('status');
        $this->setDescription('Show status of Omeka installation');
        $this->setHelp('This command shows several informations like Omeka base directory, Omeka version, database version and installed themes and plugins');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $this->getStderr()->writeln('Error: Not in an Omeka directory');

            return 1;
        }

        $omeka = $this->getOmeka();

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

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext($this->getContext());
        $plugins = $pluginInstaller->getAll();
        $active = 0;
        $installed = 0;
        $uninstalled = 0;
        foreach ($plugins as $plugin) {
            if ($plugin['id']) {
                ++$installed;
            } else {
                ++$uninstalled;
            }
            if ($plugin['active']) {
                ++$active;
            }
        }

        $output->writeln(sprintf('Installed plugins:    %1$d (%2$d active)', $installed, $active));
        $output->writeln(sprintf('Uninstalled plugins:  %d', $uninstalled));

        return 0;
    }
}
