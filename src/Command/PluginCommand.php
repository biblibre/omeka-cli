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
                    $this->logger->error('nothing to activate.');
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->activate($args[1]);
                }
                break;
            case 'de': // FALLTHROUGH
            case 'deactivate':
                if (!isset($args[1]) || $args[1] == '') {
                    $this->logger->error('nothing to deactivate.');
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->deactivate($args[1]);
                }
                break;
            case 'dl': // FALLTHROUGH
            case 'download':
                if (!isset($args[1]) || $args[1] == '') {
                    $this->logger->error('nothing to download.');
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->download($args[1]);
                }
                break;
            case 'in': // FALLTHROUGH
            case 'install':
                if (!isset($args[1]) || $args[1] == '') {
                    $this->logger->error('nothing to install.');
                    echo $this->getUsage();
                    $exitCode = 1;
                } else {
                    $exitCode = $this->install($args[1]);
                }
                break;
            case 'un': // FALLTHROUGH
            case 'uninstall':
                if (!isset($args[1]) || $args[1] == '') {
                    $this->logger->error('nothing to uninstall.');
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
                $this->logger->error('unknown argument' .$args[0] . '.');
                echo $this->getUsage();
                $exitCode = 1;
            }
        }

        return $exitCode;
    }

    protected function activate($pluginName)
    {
        $plugins = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));

        if (empty($plugins)) {
            $this->logger->error('plugin not installed.');
            return 1;
        }
        $plugin = $plugins[0];

        $missingDeps = $this->getMissingDependencies($plugin);
        if (!empty($missingDeps)) {
            $this->logger->error('missing plugins ' . implode(',', $missingDeps) . '.');
            return 1;
        }

        $broker = $plugin->getPluginBroker();
        $loader = new \Omeka_Plugin_Loader($broker,
                                           new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                           new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                           PLUGIN_DIR);
        $installer = new \Omeka_Plugin_Installer($broker, $loader);
        $installer->activate($plugin);

        $this->logger->info('plugin activated.');

        return 0;
    }

    protected function deactivate($pluginName)
    {
        $plugin = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));

        if (empty($plugin)) {
            $this->logger->error('plugin not installed.');
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

        $this->logger->info('plugin deactivated.');

        return 0;
    }

    protected function download($pluginName)
    {
        $plugins = $this->findAvailablePlugins($pluginName);
        if (empty($plugins)) {
            $this->logger->error('No plugins named ' . $pluginName . ' were found.');
            return 1;
        }

        if ($this->no_prompt) {
            if (!empty($plugins['atOmeka'])) {
                $plugin = $plugins['atOmeka'][0];
            } else {
                $this->logger->error('no such plugin at Omeka.org.');
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

            $this->logger->info('downloading from ' . $repoName . '...');
            try {
                $dest = $repo->download($plugin, $destDir);
                $this->logger->info('downloaded into ' . $dest . '...');
            } catch (\Exception $e) {
                $this->logger->error('download failed: ' . $e->getMessage());
            }
        }

        return 0;
    }

    protected function findAvailablePlugins($pluginName)
    {
        $plugins = array();

        $this->logger->info('searching on Omeka.org.');
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
            $this->logger->info('searching on GitHub.');
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

        $this->logger->info($omekaPluginCount  . ' plugin(s) found at omeka.org.');
        $this->logger->info($githubPluginCount . ' plugin(s) found at github.com.');

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
                $this->logger->info('nothing chosen.');
        }

        if (isset($chosenPlugin)) {
            if (version_compare(OMEKA_VERSION, $chosenPlugin['info']['omekaMinimumVersion']) < 0) {
                $this->logger->warning('the current Omeka version is too low to install this plugin.');
                if (!confirmPrompt('Download it anyway?'))
                    $chosenPlugin = null;
            }
        }

        return (isset($chosenPlugin)) ? $chosenPlugin : null;
    }

    protected function install($pluginName) // TODO: simplify it.
    {
        if (!$this->application->isOmekaInitialized()) {
            $this->logger->error('Omeka not initialized here.');
            return 1;
        }

        if (!file_exists(PLUGIN_DIR . '/' . $pluginName)) {
            $this->logger->error('plugin not found.');
            return 1;
        }

        $plugin = new \Plugin;
        $plugin->name = $pluginName;

        $missingDeps = $this->getMissingDependencies($plugin);
        if (!empty($missingDeps)) {
            $this->logger->error('Error: missing plugins ' . implode(',', $missingDeps));
            return 1;
        }

        $ini = parse_ini_file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini');
        $version = $ini['version'];
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
            $this->logger->info('installation succeeded.');
        else
            $this->logger->error('installation failed.');

        return 0;
    }

    protected function uninstall($pluginName)
    {
        $plugin = get_db()->getTable('Plugin')->findBy(array('name' => $pluginName));

        if (empty($plugin)) {
            $this->logger->error('plugin not installed.');
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

        $this->logger->info('plugin uninstalled.');

        return 0;
    }

    protected function update()
    {
        if (!$this->application->isOmekaInitialized()) {
            $this->logger->error('Omeka not initialized here.');
            return 1;
        }

        $c = new Client();
        foreach (get_db()->getTable('Plugin')->findAll() as $plugin) {
            if (!$this->listOnly)
                $this->logger->info('updating ' . $plugin->name);
            if (file_exists(PLUGIN_DIR . '/' . $plugin->name . '/.git')) {
                // TODO: Move github specific code to GitHub repo class.
                system('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' rev-parse @{u} 1>/dev/null 2>/dev/null', $ans);
                if ($ans != 0) {
                    $this->logger->error('no upstream.');
                    continue;
                }
                shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' fetch 2>/dev/null');
                $output = shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' log --oneline HEAD..@{u}');
                if (empty($output)) {
                    $this->logger->info('up-to-date.');
                    continue;
                }
                if ($this->listOnly) {
                    echo $plugin->name . PHP_EOL;
                    continue;
                } else {
                    $this->logger->info('new version available.');
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
                        $pluginInfo = $repo->find($plugin->name);
                        $repo->download($pluginInfo, PLUGIN_DIR);
                        if (!$this->save)
                            shell_exec('rm -rf ' . BASE_DIR . '/'. $plugin->name . '.bak');
                    } catch (\Exception $e) {
                        $this->logger->error('cannot update plugin.');
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

    protected function getMissingDependencies($plugin)
    {
        $missingDeps = array();
        $ini = parse_ini_file(PLUGIN_DIR . '/' . $plugin->name . '/plugin.ini');
        if (isset($ini['required_plugins'])) {
            $deps = $ini['required_plugins'];
            $deps = explode(',', $deps);
            $deps = array_map("trim", $deps);
            $deps = array_filter($deps);
            foreach ($deps as $dep)
                if (!plugin_is_active($dep))
                    $missingDeps[] = $dep;
        }
        return $missingDeps;
    }
}
