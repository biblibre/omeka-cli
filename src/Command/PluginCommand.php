<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\UIUtils;

use Github\Client;
use Github\Exception\RuntimeException;
use Github\Exception\ApiLimitExceedException;

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
    protected $no_prompt;
    protected $save;
    protected $listOnly;

    public function getDescription()
    {
        return 'manage plugin';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    plugin COMMAND [ARGS...]' . PHP_EOL
               . PHP_EOL
               . 'Manage plugins.' . PHP_EOL
               . PHP_EOL
               . 'COMMAND' . PHP_EOL
               . '    dl|download  {NAME}' . PHP_EOL
               . '    ac|activate {NAME}' . PHP_EOL
               . '    de|deactivate {NAME}' . PHP_EOL
               . '    in|install  {NAME}' . PHP_EOL
               . '    un|uninstall  {NAME}' . PHP_EOL
               . '    up|update [--save] [--info]' . PHP_EOL
               . PHP_EOL
               . 'The --save option of the update command will save all '
               . 'plugins before updating them into the Omeka root '
               . 'directory using name like this: "pluginName.bak".'
               . PHP_EOL
               . 'The --info option of the update command will only list '
               . 'which can be updated.'
               . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            echo $this->getUsage();
            $exitCode = 1;
        } else {
            $this->application = $application;
            $this->no_prompt = NO_PROMPT ? true : false;
            $this->save = false;
            $this->listOnly = false;

            switch ($args[0]) {
            case 'ac': // FALLTHROUGH
            case 'activate':
                if (!isset($args[1]) || $args[1] == '') {
                    echo 'Error: nothing to activate.' . PHP_EOL;
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->activate($args[1]);
                }
                break;
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
                if (isset($args[1])) {
                    if ($args[1] == '--save')
                        $this->save = true;
                    else if ($args[1] == '--list')
                        $this->listOnly = true;
                }
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

    protected function activate($pluginName)
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
        $installer->activate($plugin);

        echo 'Plugin activated.' . PHP_EOL;

        return 0;
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
            return 1;
        }

        if ($this->no_prompt) {
            if (!empty($plugins['atOmeka'])) {
                $plugin = $plugins['atOmeka'][0];
            } else {
                echo 'Error: no such plugin at Omeka.org' . PHP_EOL;
                return 1;
            }
        } else {
            $plugin = $this->pluginPrompt($plugins);
        }

        if ($plugin) {
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

        if ($this->no_prompt) {
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

    protected function install($pluginName) // TODO: simplify it.
    {
        if (!$this->application->isOmekaInitialized()) {
            echo 'Error: Omeka not initialized here.' . PHP_EOL;
            return 1;
        }

        if (!file_exists(PLUGIN_DIR . '/' . $pluginName)) {
            echo 'Error: plugin not found.' . PHP_EOL;
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
            echo 'Updating: ' . $plugin->name . PHP_EOL;
            if (file_exists(PLUGIN_DIR . '/' . $plugin->name . '/.git')) {
                // TODO: Move github specific code to GitHub repo class.
                system('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' rev-parse @{u} 1>/dev/null 2>/dev/null', $ans);
                if ($ans != 0) {
                    echo 'Error: no upstream.' . PHP_EOL;
                    continue;
                }
                shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' fetch 2>/dev/null');
                $output = shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' log --oneline HEAD..@{u}');
var_dump($output);
                if (empty($output)) {
                    echo 'up-to-date' . PHP_EOL;
                    continue;
                }
                if ($this->listOnly) {
                    echo 'new version available' . PHP_EOL;
                    continue;
                }
                shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' pull --rebase');
            } else {
                $repoClass = 'OmekaCli\Command\PluginUtil\Repository\OmekaDotOrgRepository';
                $repo = new $repoClass;
                $version = $repo->findPlugin($plugin->name)['url'];
                $tmp = preg_replace('/\.zip$/', '', preg_split('/-/', $version));
                $version = end($tmp);
                if ($plugin->version == $version) {
                    continue;
                } else {
                    if ($this->listOnly) {
                        echo $plugin->name . PHP_EOL;
                        continue;
                    }
                    shell_exec('mv ' . PLUGIN_DIR . '/' . $plugin->name . ' '
                                     . BASE_DIR   . '/' . $plugin->name . '.bak');
                    try {
                        $repo->download($plugin->name, PLUGIN_DIR);
                        if (!$this->save)
                            shell_exec('rm -rf ' . BASE_DIR . '/'. $plugin->name . '.bak');
                    } catch (\Exception $e) {
                        echo 'Error: cannot update plugin' . PHP_EOL;
                        echo $e->getMessage() . PHP_EOL;
                        shell_exec('mv ' . BASE_DIR   . '/' . $plugin->name . '.bak '
                                         . PLUGIN_DIR . '/' . $plugin->name);
                    }
                }
            }
            $broker = $plugin->getPluginBroker();
            $loader = new \Omeka_Plugin_Loader($broker,
                                              new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                              new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                              PLUGIN_DIR);
            $installer = new \Omeka_Plugin_Installer($broker, $loader);
            if (null === $plugin->getIniVersion()) {
                $version = parse_ini_file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini')['version'];
                $plugin->setIniVersion($version);
            }
            $installer->upgrade($plugin);
        }

        return 0;
    }
}
