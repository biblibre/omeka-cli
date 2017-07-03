<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\UIUtils;

require_once(__DIR__ . '/../UIUtils.php');

class PluginCommand extends AbstractCommand
{
    protected $application;

    public function getDescription()
    {
        return 'manage plugin';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
               . "\tplugin COMMAND [ARGS...]\n"
               . "\n"
               . "Manage plugins.\n"
               . "\n"
               . "COMMAND\n"
               . "\tdl|download  {NAME|URL}\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            echo $this->getUsage();
            $exitCode = 1;
        } else {
            $this->application = $application;

            switch ($args[0]) {
            case 'dl': // FALLTHROUGH
            case 'download':
                if (!isset($args[1]) || $args[1] == '') {
                    echo "Error: nothing download.\n";
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->download($args[1]);
                }
                break;
            case 'ud': // FALLTHROUGH
            case 'update':
                break;
            default:
                echo "Error: unknown argument $args[0].\n";
                echo $this->getUsage();
                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    protected function download($pluginName)
    {
        $plugins = $this->findAvailablePlugins($pluginName);
        if (empty($plugins)) {
            echo "No plugins named $pluginName were found\n";
            $exitCode = 1;
        } else if (null !== ($plugin = $this->pluginPrompt($plugins))) {
            $destDir = ($this->application->isOmekaInitialized())
                     ? PLUGIN_DIR : '.';

            $repo = $plugin['repository'];
            $repoName = $repo->getDisplayName();

            echo "Downloading from $repoName...\n";
            try {
                $dest = $repo->download($pluginName, $destDir);
                echo "Downloaded into $dest\n";
            } catch (\Exception $e) {
                echo 'Error: download failed: ' . $e->getMessage() . ".\n";
            }
            $exitCode = 0;
        } else {
            echo "Aborted.\n";
            $exitCode = 1;
        }

        return $exitCode;
    }

    protected function findAvailablePlugins($pluginName)
    {
        $plugins = array();

        echo "Searching on Omeka.org\n";
        $repoClass = 'OmekaCli\Command\PluginUtil\Repository\OmekaDotOrgRepository';
        $repo = new $repoClass;
        $pluginInfo = $repo->find($pluginName);
        if ($pluginInfo) {
            $pluginsOmeka[] = array(
                'info'       => $pluginInfo,
                'repository' => $repo,
            );
        } else {
            $pluginsOmeka = array();
        }

        echo "Searching on GitHub\n";
        $repoClass = 'OmekaCli\Command\PluginUtil\Repository\GithubRepository';
        $repo = new $repoClass;
        $pluginInfo = $repo->find($pluginName);
        if (!empty($pluginInfo)) {
            foreach ($pluginInfo as $info) {
                $pluginsGitHub[] = array(
                    'info'       => $info,
                    'repository' => $repo,
                );
            }
        }

        return array(
            'atOmeka'  => empty($pluginsOmeka)  ? array() : $pluginsOmeka,
            'atGithub' => empty($pluginsGitHub) ? array() : $pluginsGitHub,
        );
    }

    protected function pluginPrompt($plugins)
    {
        $omekaPluginCount  = count($plugins['atOmeka']);
        $githubPluginCount = count($plugins['atGithub']);

        echo $omekaPluginCount  . ' plugin(s) found at omeka.org'  . "\n";
        echo $githubPluginCount . ' plugin(s) found at github.com' . "\n";

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
                    $plugin['repository']->getDisplayName()
                );
            }

            if (isset($toMenu)) {
                $chosenIdx = UIUtils::menuPrompt('Choose one', $toMenu);
                if ($chosenIdx > 0) {
                    $repoClass = 'OmekaCli\Command\PluginUtil\Repository\GithubRepository';
                    $repoClass::setUrl($allPlugins[$chosenIdx]['info']['url']); // TODO change it, this is madness.
                }
            }

            if ($chosenIdx >= 0)
                $chosenPlugin = $allPlugins[$chosenIdx];
            else
                echo 'Nothing chosen.' . "\n";
        }

        if (version_compare(OMEKA_VERSION, $chosenPlugin['info']['omekaMinimumVersion']) < 0) {
            echo 'Warning: the current Omeka version is to low to install'
               . 'this plugin.' . PHP_EOL;
            if (!confirmPrompt('Download it anyway?'))
                $chosenPlugin = null;
        }

        return $chosenPlugin;
    }
}
