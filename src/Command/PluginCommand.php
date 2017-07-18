<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\UIUtils;

use Github\Client;
use Github\Exception\RuntimeException;

use GetOptionKit\ContinuousOptionParser;
use GetOptionKit\Exception\InvalidOptionException;
use GetOptionKit\OptionCollection;

use Omeka\Plugin;
use Omeka\Plugin\Broker;
use Omeka\Plugin\Installer;

use Omeka\Record;

require_once(__DIR__ . '/../UIUtils.php');

class PluginCommand extends AbstractCommand
{
    protected $application;
    protected $quick;

    public function getOptionsSpec()
    {
        $appSpec = new OptionCollection;
        $appSpec->add('q|quick', 'do not prompt anything, just go ahead.');

        return $appSpec;
    }

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
               . "\tdl|download  [-q|--quick]  {NAME}\n"
               . "\tde|deactivate {NAME}\n"
               . "\tin|install  {NAME}\n"
               . "\tun|uninstall  {NAME}\n"
               . "\tup|update  [-q|--quick]\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            echo $this->getUsage();
            $exitCode = 1;
        } else {
            $this->application = $application;
            $this->quick = isset($options['quick']) ? true : false;

            switch ($args[0]) {
            case 'de': // FALLTHROUGH
            case 'deactivate':
                if (!isset($args[1]) || $args[1] == '') {
                    echo 'Error: nothing to deactivate.' . PHP_EOL;
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->deactivate($args[1]);
                }
                break;
            case 'dl': // FALLTHROUGH
            case 'download':
                if (!isset($args[1]) || $args[1] == '') {
                    echo 'Error: nothing to download.' . PHP_EOL;
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->download($args[1]);
                }
                break;
            case 'in': // FALLTHROUGH
            case 'install':
                if (!isset($args[1]) || $args[1] == '') {
                    echo 'Error: nothing to install.' . PHP_EOL;
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->install($args[1]);
                }
                break;
            case 'un': // FALLTHROUGH
            case 'uninstall':
                if (!isset($args[1]) || $args[1] == '') {
                    echo 'Error: nothing to uninstall.' . PHP_EOL;
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->uninstall($args[1]);
                }
                break;
            case 'up': // FALLTHROUGH
            case 'update':
                $exitCode = $this->update();
                break;
            default:
                echo 'Error: unknown argument $args[0].' . PHP_EOL;
                echo $this->getUsage();
                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    protected function deactivate($pluginName)
    {
        $plugin = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));

        if (empty($plugin)) {
            echo 'Error: plugin not installed.' . PHP_EOL;
            return 1;
        }
        $plugin = $plugin[0];

        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->deactivate($plugin);

        echo 'Plugin deactivated.' . PHP_EOL;

        return 0;
    }

    protected function download($pluginName)
    {
        $plugins = $this->findAvailablePlugins($pluginName);
        if (empty($plugins)) {
            echo "No plugins named $pluginName were found\n";
            $exitCode = 1;
        } else if ($this->quick || null !== ($plugin = $this->pluginPrompt($plugins))) {
            $destDir = ($this->application->isOmekaInitialized())
                     ? PLUGIN_DIR : '.';

            if (!empty($plugins['atOmeka'])) {
                $plugin = $plugins['atOmeka']['0'];
            } else {
                echo 'Error: no such plugin at Omeka.org' . PHP_EOL;
                return 1;
            }
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

        if ($this->quick) {
            $pluginsGitHub = array();
        } else {
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
                    (array_key_exists('owner', $plugin['info']))
                        ? $plugin['repository']->getDisplayName()
                            . '/' . $plugin['info']['owner']
                        : $plugin['repository']->getDisplayName()
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

        if (isset($chosenPlugin)) {
            if (version_compare(OMEKA_VERSION, $chosenPlugin['info']['omekaMinimumVersion']) < 0) {
                echo 'Warning: the current Omeka version is to low to install'
                   . 'this plugin.' . PHP_EOL;
                if (!confirmPrompt('Download it anyway?'))
                    $chosenPlugin = null;
            }
        }

        return (isset($chosenPlugin)) ? $chosenPlugin : null;
    }

    protected function install($pluginName)
    {
        if (!$this->application->isOmekaInitialized()) {
            echo 'Error: Omeka not initialized here.' . PHP_EOL;
            return 1;
        }

        $plugin = new \Plugin;
        $plugin->name = $pluginName;

        $version = array_filter(
            file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini'),
            function($var) { return preg_match('/\Aversion=/', $var); }
        );
        $version = array_pop($version);
        $version = preg_replace('/\Aversion=/', '', $version);
        $version = preg_replace('/"/', '', $version);
        $plugin->setIniVersion($version);
        $plugin->setLoaded(true);
        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->install($plugin);

        $result = get_db()->getTable('Plugin')->findBy(array(
            'active' => 1,
            'name'   => $plugin->name,
        ))[0];

        if ($result->isActive())
            echo 'Installation succeeded.' . PHP_EOL;
        else
            echo 'Installation failed.' . PHP_EOL;

        return 0;
    }

    protected function uninstall($pluginName)
    {
        $plugin = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));

        if (empty($plugin)) {
            echo 'Error: plugin not installed.' . PHP_EOL;
            return 1;
        }
        $plugin = $plugin[0];
        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->uninstall($plugin);

        echo 'Plugin uninstalled.' . PHP_EOL;

        return 0;
    }

    protected function update()
    {
        if (!$this->application->isOmekaInitialized()) {
            echo 'Error: Omeka not initialized here.' . PHP_EOL;
            return 1;
        }

        $c = new Client();
        foreach (get_db()->getTable('Plugin')->findAll() as $plugin) {
            if (file_exists('plugins/' . $plugin->name . '/.git/config')) {
                $localCommitHash = rtrim(shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' rev-parse HEAD'), PHP_EOL);
                $author = explode('/', shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' config --get remote.origin.url'))[3];
                try {
                    $remoteCommitHash = $c->api('repo')->commits()->all($author, $plugin->name, array())[0]['sha'];
                } catch (\RuntimeException $e) {
                    echo $e->getMessage() . PHP_EOL;
                    continue;
                }
                if ($localCommitHash == $remoteCommitHash)
                    continue;
                else
                    shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' pull');
            } else {
                $repoClass = 'OmekaCli\Command\PluginUtil\Repository\OmekaDotOrgRepository';
                $repo = new $repoClass;
                $version = $repo->findPlugin($plugin->name)['url'];
                $tmp = preg_replace('/\.zip$/', '', preg_split('/-/', $version));
                $version = end($tmp);
                if ($plugin->version == $version) {
                    continue;
                } else {
                    $this->quick = true;
                    shell_exec('rm -r ' . PLUGIN_DIR . '/'. $plugin->name);
                    ob_start();
                    $this->download($plugin->name);
                    ob_end_clean();
                 }
            }
            $pluginsToUpdate[] = $plugin;
        }

        if (!empty($pluginsToUpdate)) {
            echo 'Updating...' . PHP_EOL;
            foreach($pluginsToUpdate as $plugin) {
                echo $plugin->name;
                if (!$this->quick && !UIUtils::confirmPrompt(', update?'))
                    continue;
                else
                    echo PHP_EOL;
                $broker = $plugin->getPluginBroker();
                $loader = new \Omeka_Plugin_Loader($broker,
                                                  new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                                  new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                                  PLUGIN_DIR);
                $installer = new \Omeka_Plugin_Installer($broker, $loader);
                if (null === $plugin->getIniVersion()) {
                    $version = array_filter(
                        file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini'),
                        function($var) { return preg_match('/\Aversion=/', $var); }
                    );
                    $version = array_pop($version);
                    $version = preg_replace('/\Aversion=/', '', $version);
                    $version = preg_replace('/"/', '', $version);
                    $plugin->setIniVersion($version);
                }
                $installer->upgrade($plugin);
            }
        }

        return 0;
    }
}
