<?php

namespace OmekaCli\Command;

use Zend_Registry;
use OmekaCli\Application;
use OmekaCli\Command\PluginCommands\Update;
use OmekaCli\Plugin\Updater;

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

    public function run($options, $args, Application $application)
    {
        # TODO Use GitHub releases API
        $remoteTag = rtrim(`git ls-remote -q --tags --refs https://github.com/biblibre/omeka-cli | cut -f 2 | sed "s|refs/tags/||" | sort -rV | head -n1`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (OMEKACLI_VERSION === $remoteVersion) {
            $this->logger->info('omeka-cli is up-to-date ({version})', array('version' => OMEKACLI_VERSION));
        } else {
            echo sprintf('omeka-cli (%s -> %s)', OMEKACLI_VERSION, $remoteVersion) . PHP_EOL;
        }

        if ($application->isOmekaInitialized()) {
            $latestOmekaVersion = latest_omeka_version();
            if (version_compare(OMEKA_VERSION, $latestOmekaVersion) >= 0) {
                $this->logger->info('Omeka is up-to-date ({version})', array('version' => OMEKA_VERSION));
            } else {
                echo sprintf('Omeka (%s -> %s)', OMEKA_VERSION, $latestOmekaVersion) . PHP_EOL;
            }

            $updater = new Updater();
            $updater->setLogger($this->logger);
            $pluginLoader = Zend_Registry::get('plugin_loader');
            $plugins = $pluginLoader->getPlugins();
            foreach ($plugins as $plugin) {
                $latestVersion = $updater->getPluginLatestVersion($plugin);
                if (version_compare($latestVersion, $plugin->getIniVersion()) > 0) {
                    echo sprintf('%s (%s -> %s)', $plugin->name, $plugin->getIniVersion(), $latestVersion) . PHP_EOL;
                } else {
                    $this->logger->info('{plugin} is up-to-date ({version})', array('plugin' => $plugin->name, 'version' => $plugin->getIniVersion()));
                }
            }
        }
    }
}
