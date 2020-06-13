<?php

namespace OmekaCli\Command;

use OmekaCli\Omeka;
use OmekaCli\Plugin\Updater;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zend_Registry;

class CheckUpdatesCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('check-updates');
        $this->setDescription('tell if omeka-cli, Omeka and its plugins are up-to-date');
        $this->setAliases(['chkup']);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        // TODO Use GitHub releases API
        $remoteTag = rtrim(`git ls-remote -q --tags --refs https://github.com/biblibre/omeka-cli | cut -f 2 | sed "s|refs/tags/||" | sort -rV | head -n1`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (OMEKACLI_VERSION === $remoteVersion) {
            if ($stderr->isVerbose()) {
                $stderr->writeln(sprintf('omeka-cli is up-to-date (%s)', OMEKACLI_VERSION));
            }
        } else {
            $output->writeln(sprintf('omeka-cli (%s -> %s)', OMEKACLI_VERSION, $remoteVersion));
        }

        if ($this->getContext()->getOmekaPath()) {
            $omeka = new Omeka();
            $omeka->setContext($this->getContext());
            $omekaVersion = $omeka->OMEKA_VERSION;
            $latestOmekaVersion = $omeka->latest_omeka_version();
            if (version_compare($omekaVersion, $latestOmekaVersion) >= 0) {
                if ($stderr->isVerbose()) {
                    $stderr->writeln(sprintf('Omeka is up-to-date (%s)', $omekaVersion));
                }
            } else {
                $output->writeln(sprintf('Omeka (%s -> %s)', $omekaVersion, $latestOmekaVersion));
            }

            $updater = new Updater();
            $updater->setOutput($stderr);
            $updater->setContext($this->getContext());
            $plugins = $this->getSandbox()->execute(function () {
                $pluginLoader = Zend_Registry::get('plugin_loader');

                return array_map(function ($plugin) {
                    return array_merge(
                        $plugin->toArray(),
                        ['ini_version' => $plugin->getIniVersion()]
                    );
                }, $pluginLoader->getPlugins());
            });

            foreach ($plugins as $plugin) {
                $latestVersion = $updater->getPluginLatestVersion($plugin['name']);
                if (version_compare($latestVersion, $plugin['ini_version']) > 0) {
                    $output->writeln(sprintf('%s (%s -> %s)', $plugin['name'], $plugin['ini_version'], $latestVersion));
                } else {
                    if ($stderr->isVerbose()) {
                        $stderr->writeln(sprintf('%1$s is up-to-date (%2$s)', $plugin['name'], $plugin['ini_version']));
                    }
                }
            }
        }
    }
}
