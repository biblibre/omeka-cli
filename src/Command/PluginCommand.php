<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;

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
            case 'dl': /* FALLTHROUGH */
            case 'download':
                if (!isset($args[1]) || $args[1] == '') {
                    echo "Error: nothing download.\n";
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->download($args[1]);
                }
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
        if ($pluginInfo) {
            $pluginsGitHub[] = array(
                'info'       => $pluginInfo,
                'repository' => $repo,
            );
        } else {
            $pluginsGitHub = array();
        }

        return array(
            'atOmeka'  => $pluginsOmeka,
            'atGithub' => $pluginsGitHub,
        );
    }

    protected function pluginPrompt($plugins)
    {
        $omekaPluginCount  = count($plugins['atOmeka']);
        $githubPluginCount = count($plugins['atGithub']);

        echo $omekaPluginCount . ' plugin(s) found at omeka.org' . "\n";
        foreach ($plugins['atOmeka'] as $plugin)
            echo $plugin['info']['name'] . "\n";

        echo $githubPluginCount . ' plugin(s) found at github.com' . "\n";
        foreach ($plugins['atGithub'] as $plugin)
            echo $plugin['info']['name'] . "\n";

        echo 'Download from omeka.org or github.com?' . "\n";
        $ans = $this->menuPrompt('Choose one', array('omeka', 'github'));
        if (isset($ans)) {
            switch ($ans) {
            case 0:
                $chosenPlugin = $plugins['atOmeka'][0];
                break;
            case 1:
                $chosenPlugin = $plugins['atGithub'][0];
                break;
            default:
                echo 'Error: something is going wrong during pluginPrompt.' . "\n";
            }
        } else {
            echo 'Nothing chosen.' . "\n";
        }

        return isset($chosenPlugin) ? $chosenPlugin : null;
    }

    protected function confirmPrompt($text) // TODO move it, it's not a plugin specific task.
    {
        do {
            echo "$text [y,n] ";
            $ans = trim(fgets(STDIN));
        } while ($ans != 'y' && $ans != 'n');

        return $ans == 'y' ? true : false;
    }

    protected function menuPrompt($text, $options) // TODO move it, it's not a plugin specific task.
    {
        do {
            $i = 0;
            foreach ($options as $option) {
                echo "[$i] $option\n";
                ++$i;
            }
            $max = $i - 1;
            echo "$text [0-$max,q] ";
            $ans = trim(fgets(STDIN));
        } while ((!is_numeric($ans) || $ans < 0 || $ans > $max) &&
                 $ans != 'q');

        return $ans != 'q' ? $ans : null;
    }
}
