<?php

namespace OmekaCli\Command\PluginCommands;

use GetOptionKit\OptionCollection;
use OmekaCli\Application;
use OmekaCli\UIUtils;
use OmekaCli\Command\PluginCommands\Utils\Repository\OmekaDotOrgRepository;
use OmekaCli\Command\PluginCommands\Utils\Repository\GithubRepository;

class Download extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'download a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-download [<options>] PLUGIN_NAME\n"
             . "\tpldl [<options>] PLUGIN_NAME\n"
             . "\n"
             . "Options:\n"
             . "\t-f, --force    Force download, even if Omeka minimum version\n"
             . "\t               requirement is not met\n"
             . "\t-G, --exclude-github    Do not download plugins from Github\n";
    }

    public function getOptionsSpec()
    {
        $optionsSpec = new OptionCollection();
        $optionsSpec->add('f|force', 'Force download');
        $optionsSpec->add('G|exclude-github', 'Do not download plugins from Github');

        return $optionsSpec;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) != 1) {
            $this->logger->error($this->getUsage());

            return 1;
        }

        $repositories = array();
        $repositories[] = new OmekaDotOrgRepository();
        if (!isset($options['exclude-github']) || !$options['exclude-github']) {
            $repositories[] = new GithubRepository();
        }

        $pluginName = reset($args);

        foreach ($repositories as $repository) {
            $pluginsInfo = $repository->find($pluginName);
            foreach ($pluginsInfo as $pluginInfo) {
                $plugins[] = array(
                    'info' => $pluginInfo,
                    'repository' => $repository,
                );
            }
        }

        if (empty($plugins)) {
            $this->logger->error('plugin not found');

            return 1;
        }

        if (1 === count($plugins)) {
            $plugin = reset($plugins);
        } else {
            $plugin = $this->pluginPrompt($plugins);
        }

        if (!$plugin) {
            $this->logger->info('Nothing downloaded');

            return 0;
        }

        $destDir = '.';

        if ($application->isOmekaInitialized()) {
            $omekaMinimumVersion = $plugin['info']['omekaMinimumVersion'];
            $force = isset($options['force']) && $options['force'];
            if (version_compare(OMEKA_VERSION, $omekaMinimumVersion) < 0 && !$force) {
                $this->logger->error('The current Omeka version is too low to install this plugin. Use --force if you really want to download it.');
                return 1;
            }

            $destDir = PLUGIN_DIR;
        }

        $this->logger->info('Downloading plugin');
        try {
            $repository = $plugin['repository'];
            $dest = $repository->download($plugin['info'], $destDir);
            $this->logger->info('Downloaded into {path}', array('path' => $dest));
        } catch (\Exception $e) {
            $this->logger->error('Download failed: {message}', array('message' => $e->getMessage()));
        }

        return 0;
    }

    protected function pluginPrompt($plugins)
    {
        if (empty($plugins)) {
            return null;
        }

        $this->logger->info('{count} plugin(s) found', array('count' => count($plugins)));

        foreach ($plugins as $plugin) {
            $toMenu[] = sprintf('%s (%s) - %s',
                $plugin['info']['displayName'],
                $plugin['info']['version'],
                (array_key_exists('owner', $plugin['info']))
                    ? $plugin['repository']->getDisplayName()
                        . '/' . $plugin['info']['owner']
                    : $plugin['repository']->getDisplayName()
            );
        }

        if (isset($toMenu)) {
            $chosenIdx = UIUtils::menuPrompt('Choose one', $toMenu);
        }

        if ($chosenIdx >= 0) {
            $chosenPlugin = $plugins[$chosenIdx];

        }

        return (isset($chosenPlugin)) ? $chosenPlugin : null;
    }
}
