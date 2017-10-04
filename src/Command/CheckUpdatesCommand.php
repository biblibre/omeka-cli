<?php

namespace OmekaCli\Command;

use Zend_Registry;
use OmekaCli\Plugin\Updater;
use OmekaCli\Omeka;

class CheckUpdatesCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'tell if omeka-cli, Omeka and its plugins are up-to-date';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    check-updates|chup' . PHP_EOL
               . PHP_EOL
               . 'Tell if omeka-cli, Omeka and its plugins are up-to-date.'
               . PHP_EOL;

        return $usage;
    }

    public function run($options, $args)
    {
        // TODO Use GitHub releases API
        $remoteTag = rtrim(`git ls-remote -q --tags --refs https://github.com/biblibre/omeka-cli | cut -f 2 | sed "s|refs/tags/||" | sort -rV | head -n1`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (OMEKACLI_VERSION === $remoteVersion) {
            $this->logger->info('omeka-cli is up-to-date ({version})', array('version' => OMEKACLI_VERSION));
        } else {
            echo sprintf('omeka-cli (%s -> %s)', OMEKACLI_VERSION, $remoteVersion) . PHP_EOL;
        }

        if ($this->context->getOmekaPath()) {
            $omeka = new Omeka();
            $omeka->setContext($this->getContext());
            $omekaVersion = $omeka->OMEKA_VERSION;
            $latestOmekaVersion = $omeka->latest_omeka_version();
            if (version_compare($omekaVersion, $latestOmekaVersion) >= 0) {
                $this->logger->info('Omeka is up-to-date ({version})', array('version' => $omekaVersion));
            } else {
                echo sprintf('Omeka (%s -> %s)', $omekaVersion, $latestOmekaVersion) . "\n";
            }

            $updater = new Updater();
            $updater->setLogger($this->logger);
            $updater->setContext($this->getContext());
            $plugins = $this->getSandbox()->execute(function () {
                $pluginLoader = Zend_Registry::get('plugin_loader');

                return array_map(function ($plugin) {
                    return array_merge(
                        $plugin->toArray(),
                        array('ini_version' => $plugin->getIniVersion())
                    );
                }, $pluginLoader->getPlugins());
            });

            foreach ($plugins as $plugin) {
                $latestVersion = $updater->getPluginLatestVersion($plugin['name']);
                if (version_compare($latestVersion, $plugin['ini_version']) > 0) {
                    echo sprintf('%s (%s -> %s)', $plugin['name'], $plugin['ini_version'], $latestVersion) . "\n";
                } else {
                    $this->logger->info('{plugin} is up-to-date ({version})', array('plugin' => $plugin['name'], 'version' => $plugin['ini_version']));
                }
            }
        }
    }
}
