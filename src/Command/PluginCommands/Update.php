<?php

namespace OmekaCli\Command\PluginCommands;

use OmekaCli\Application;
use OmekaCli\Command\AbstractCommand;
use GetOptionKit\OptionCollection;
use Github\Client;

class Update extends AbstractCommand
{
    public function getOptionsSpec()
    {
        $cmdSpec = new OptionCollection();
        $cmdSpec->add('list', 'list plugins to update only');

        return $cmdSpec;
    }

    public function getDescription()
    {
        return 'update plugins';
    }

    public function getUsage()
    {
        return 'usage:' . PHP_EOL
             . '    plugin-update [--list] [PLUGIN_NAME]' . PHP_EOL
             . '    plup [--list] [PLUGIN_NAME]' . PHP_EOL
             . PHP_EOL
             . 'Update plugins. Use the --list option to get the list of '
             . 'plugins to update without updating them.' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        if (!empty($options)) {
            $listOnly = $options['list'];
        } else {
            $listOnly = false;
        }
        switch (count($args)) {
        case 0:
            $pluginName = null;
            break;
        case 1:
            $pluginName = array_pop($args);
            break;
        default:
            $this->logger->error($this->getUsage());

            return 1;
        }

        if (!$application->isOmekaInitialized()) {
            $this->logger->error('Omeka not initialized here.');

            return 1;
        }
        $c = new Client();
        $plugins = get_db()->getTable('Plugin')->findBy($pluginName == null ? array() : array('name' => $pluginName));
        foreach ($plugins as $plugin) {
            if (!$listOnly) {
                $this->logger->info('updating ' . $plugin->name);
            }
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
                    $this->logger->info($plugin->name . ' is up-to-date');
                    continue;
                }
                if ($listOnly) {
                    echo $plugin->name . ' can be updated' . PHP_EOL;
                    continue;
                } else {
                    $this->logger->info('new version available.');
                }
                shell_exec('git -C ' . PLUGIN_DIR . '/' . $plugin->name . ' pull --rebase');
            } else {
                $repoClass = 'OmekaCli\Command\PluginCommands\Utils\Repository\OmekaDotOrgRepository';
                $repo = new $repoClass();
                $version = $repo->findPlugin($plugin->name)['url'];
                $tmp = preg_replace('/\.zip$/', '', preg_split('/-/', $version));
                $version = end($tmp);
                if ($plugin->version == $version) {
                    continue;
                } else {
                    if ($listOnly) {
                        echo $plugin->name . ' can be updated' . PHP_EOL;
                        continue;
                    }
                    $backDir = getenv('HOME') . '/.omeka-cli/backups';
                    if (!is_dir($backDir)) {
                        if (!mkdir($backDir, 0755, true)) {
                            if (!UIUtils::confirmPrompt('Cannot create backups directory. Anyway?')) {
                                continue;
                            }
                        }
                    }
                    shell_exec('mv ' . PLUGIN_DIR . '/' . $plugin->name . ' '
                                     . $backDir . '/' . $plugin->name . '_' . date('YmdHi'));
                    try {
                        $pluginInfo = $repo->find($plugin->name);
                        $repo->download($pluginInfo, PLUGIN_DIR);
                    } catch (\Exception $e) {
                        $this->logger->error('cannot update plugin.');
                        echo $e->getMessage() . PHP_EOL;
                        shell_exec('mv ' . $backDir . '/' . $plugin->name . '_' . date('YmdHi') . ' '
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
