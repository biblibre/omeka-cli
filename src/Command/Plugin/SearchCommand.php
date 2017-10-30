<?php

namespace OmekaCli\Command\Plugin;

use GetOptionKit\OptionCollection;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use OmekaCli\Plugin\Repository\GithubRepository;

class SearchCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'search plugins';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-search [<options>] <query>\n"
             . "\tpls [<options>] <query>\n"
             . "\n"
             . "Options:\n"
             . "\t-G, --exclude-github    Do not search plugins in Github\n";
    }

    public function getOptionsSpec()
    {
        $optionsSpec = new OptionCollection();
        $optionsSpec->add('G|exclude-github', 'Do not search plugins in Github');

        return $optionsSpec;
    }

    public function run($options, $args)
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

        $query = reset($args);

        $plugins = array();
        foreach ($repositories as $repository) {
            $pluginsInfo = $repository->search($query);
            foreach ($pluginsInfo as $pluginInfo) {
                $plugins[] = array(
                    'info' => $pluginInfo,
                    'repository' => $repository,
                );
            }
        }

        foreach ($plugins as $plugin) {
            echo sprintf("%s (%s) - %s\n",
                $plugin['info']['id'],
                $plugin['info']['version'],
                $plugin['repository']->getDisplayName()
            );
        }

        return 0;
    }
}
