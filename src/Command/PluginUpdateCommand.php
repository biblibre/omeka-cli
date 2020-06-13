<?php

namespace OmekaCli\Command;

use OmekaCli\Plugin\Updater;
use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginUpdateCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('plugin-update');
        $this->setDescription('update plugins');
        $this->setAliases(['up']);
        $this->addArgument('name', InputArgument::REQUIRED, 'the name of plugin to update');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $stderr->writeln('Error: Not in an Omeka directory');

            return 1;
        }

        $pluginName = $input->getArgument('name');

        try {
            $iniVersion = $this->getSandbox()->execute(function () use ($pluginName) {
                $pluginLoader = \Zend_Registry::get('plugin_loader');
                $plugin = $pluginLoader->getPlugin($pluginName);
                if (!isset($plugin)) {
                    throw new \Exception("Plugin $pluginName not found");
                }

                return $plugin->getIniVersion();
            });

            $updater = new Updater();
            $updater->setOutput($stderr);
            $updater->setContext($this->getContext());

            $latestVersion = $updater->getPluginLatestVersion($pluginName);
            if (version_compare($latestVersion, $iniVersion) <= 0) {
                $stderr->writeln(sprintf('Error: %1$s is up-to-date (%2$s)', $pluginName, $iniVersion));

                return 1;
            }

            if ($stderr->isVerbose()) {
                $stderr->writeln(sprintf('Updating %s', $pluginName));
            }

            if (!$updater->update($pluginName)) {
                $stderr->writeln('Error: Plugin update failed');

                return 1;
            }

            $sandbox = new OmekaSandbox();
            $sandbox->setContext($this->getContext());
            $sandbox->execute(function () use ($pluginName) {
                $pluginLoader = \Zend_Registry::get('plugin_loader');
                $pluginInstaller = new \Omeka_Plugin_Installer(
                    \Zend_Registry::get('pluginbroker'),
                    $pluginLoader
                );

                $plugin = $pluginLoader->getPlugin($pluginName);

                $iniReader = new \Omeka_Plugin_Ini(PLUGIN_DIR);
                $iniReader->load($plugin);

                $iniVersion = $plugin->getIniVersion();
                $pluginInstaller->upgrade($plugin);
            }, OmekaSandbox::ENV_SHORTLIVED);
            $stderr->writeln(sprintf('Plugin %1$s upgraded successfully to %2$s', $pluginName, $iniVersion));
        } catch (\Exception $e) {
            $stderr->writeln('Error: ' . $e->getMessage());

            return 1;
        }

        return 0;
    }
}
