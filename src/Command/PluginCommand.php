<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;

class PluginCommand extends AbstractCommand
{
    protected static $repositories = array(
        'omeka.org'    => 'OmekaCli\Plugin\Repository\OmekaDotOrgRepository',
        'github:omeka' => 'OmekaCli\Plugin\Repository\GithubOmekaRepository',
        'github:ucsc'  => 'OmekaCli\Plugin\Repository\GithubUcscRepository',
    );

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
            return 1;
        }

        $logger = $application->getLogger();

        switch ($args[0]) {
        case 'dl': /* FALLTHROUGH */
        case 'download':
            if (!isset($args[1]) || $args[1] == '') {
                echo "Error: missing or empty argument to download.\n";
                echo $this->getUsage();
                return 1;
            }
            $exitCode = $this->download($args[1]);
            break;
        default:
            echo "Error: unknown argument $args[0].\n";
            echo $this->getUsage();
            return 1;
        }

        return $exitCode;
    }

    protected function download($pluginName)
    {
        $plugins = $this->findAvailablePlugins($pluginName);
        if (empty($plugins)) {
            echo "No plugins named $pluginName were found\n";

            return 1;
        }

        $plugin = $this->pluginPrompt($plugins);
        if (!isset($plugin)) {
            return 0;
        }

        if ($application->isOmekaInitialized()) {
            $destDir = PLUGIN_DIR;
        } else {
            $destDir = '.';
        }

        $repository = $plugin['repository'];
        $repositoryName = $repository->getDisplayName();

        echo "Downloading $pluginName from $repositoryName...\n";
        try {
            $dest = $repository->download($pluginName, $destDir);
            echo "Downloaded into $dest\n";
        } catch (\Exception $e) {
            echo "Download failed.\n";
        }
    }

    protected function findAvailablePlugins($pluginName)
    {
        $plugins = array();

        foreach (self::$repositories as $name => $repositoryClass) {
            $repository = new $repositoryClass();

            $repositoryName = $repository->getDisplayName();
            echo "Searching $pluginName in $repositoryName\n";
            $pluginInfo = $repository->find($pluginName);
            if ($pluginInfo) {
                $plugins[] = array(
                    'info' => $pluginInfo,
                    'repository' => $repository,
                );
            }
        }

        return $plugins;
    }

    protected function pluginPrompt($plugins)
    {
        $pluginIdx = null;

        if (count($plugins) == 1) {
            $info = $plugins[0]['info'];
            $repository = $plugins[0]['repository'];
            echo sprintf('Found plugin %s (%s) on %s', $info->displayName,
                $info->version, $repository->getDisplayName()) . "\n";
            if ($this->confirmPrompt('Do you want to download it ?')) {
                $pluginIdx = 0;
            }
        } else {
            echo sprintf('Found %s plugins', count($plugins)) . "\n";

            $pluginOptions = array();
            foreach ($plugins as $plugin) {
                $info = $plugin['info'];
                $repositoryName = $plugin['repository']->getDisplayName();

                $pluginOptions[] = sprintf('%s (%s) from %s',
                    $info->displayName, $info->version, $repositoryName);
            }

            $result = $this->menuPrompt('Choose one', $pluginOptions);
            if (is_numeric($result) && isset($pluginOptions[$result])) {
                $pluginIdx = $result;
            }
        }

        if (isset($pluginIdx)) {
            return $plugins[$pluginIdx];
        }
    }

    protected function confirmPrompt($text)
    {
        do {
            echo "$text [y,n] ";
            $response = trim(fgets(STDIN));
        } while ($response != 'y' && $response != 'n');

        return $response == 'y' ? true : false;
    }

    protected function menuPrompt($text, $options)
    {
        do {
            $i = 0;
            foreach ($options as $option) {
                echo "[$i] $option\n";
                ++$i;
            }
            $max = $i - 1;
            echo "$text [0-$max,q] ";
            $response = trim(fgets(STDIN));
        } while ((!is_numeric($response) || $response < 0 || $response > $max) && $response != 'q');

        return $response != 'q' ? $response : null;
    }
}
