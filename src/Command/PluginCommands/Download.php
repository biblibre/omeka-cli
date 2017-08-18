<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Command\PluginCommands\Utils\PluginUtils as PUtils;
use OmekaCli\UIUtils;

class Download extends AbstractCommand
{
    public function getDescription()
    {
        return 'download a plugin';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-download PLUGIN_NAME' . PHP_EOL
             . '    pldl PLUGIN_NAME' . PHP_EOL
             . PHP_EOL
             . 'Download a plugin' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            $this->logger->error($this->getUsage());
            return 1;
        }

        $plugins = PUtils::findAvailablePlugins(array_shift($args), NO_PROMPT);
        if (empty($plugins)) {
            $this->logger->error('plugin not found');
            return 1;
        }

        if (NO_PROMPT) {
            if (empty($plugins['atOmeka'])) {
                $this->logger->error('plugin not found');
                return 1;
            }
            $plugin = $plugins['atOmeka'][0];
        } else {
            $plugin = $this->pluginPrompt($plugins);
        }

        if (!$plugin) {
            $this->logger->info('Nothing downloaded');
            return 0;
        }
        $destDir = ($application->isOmekaInitialized()) ? PLUGIN_DIR : '';
        $repo = $plugin['repository'];
        $repoName = $repo->getDisplayName();

        $this->logger->info('Downloading plugin');
        try {
            $dest = $repo->download($plugin['info'], $destDir);
            $this->logger->info('Downloaded into ' . $dest);
        } catch (\Exception $e) {
            $this->logger->error('download failed: ' . $e->getMessage());
        }

        return 0;
    }

    protected function pluginPrompt($plugins)
    {
        $omekaPluginCount  = count($plugins['atOmeka']);
        $githubPluginCount = count($plugins['atGithub']);

        $this->logger->info($omekaPluginCount  . ' plugin(s) found at omeka.org');
        $this->logger->info($githubPluginCount . ' plugin(s) found at github.com');

        if (!empty($plugins['atOmeka']) && !empty($plugins['atGithub']))
            $allPlugins = array_merge($plugins['atOmeka'], $plugins['atGithub']);
        else if (empty($plugin['atGithub']))
            $allPlugins = $plugins['atOmeka'];
        else if (empty($plugin['atOmeka']))
            $allPlugins = $plugins['atGithub'];

        if (count($allPlugins) != 0) {
            foreach ($allPlugins as $plugin) {
                $toMenu[] = sprintf("%s (%s) - %s",
                    $plugin['info']['displayName'],
                    $plugin['info']['version'],
                    (array_key_exists('owner', $plugin['info']))
                        ? $plugin['repository']->getDisplayName()
                            . '/' . $plugin['info']['owner']
                        : $plugin['repository']->getDisplayName()
                );
            }

            if (isset($toMenu))
                $chosenIdx = UIUtils::menuPrompt('Choose one', $toMenu);

            if ($chosenIdx >= 0)
                $chosenPlugin = $allPlugins[$chosenIdx];
            else
                $this->logger->info('Nothing chosen');
        }

        if (isset($chosenPlugin)) {
            if (version_compare(OMEKA_VERSION, $chosenPlugin['info']['omekaMinimumVersion']) < 0) {
                $this->logger->warning('the current Omeka version is too low to install this plugin');
                if (!confirmPrompt('Download it anyway?'))
                    $chosenPlugin = null;
            }
        }

        return (isset($chosenPlugin)) ? $chosenPlugin : null;
    }
}
