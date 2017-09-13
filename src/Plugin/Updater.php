<?php

namespace OmekaCli\Plugin;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use OmekaCli\Command\PluginCommands\Utils\Repository\OmekaDotOrgRepository;

class Updater implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    public function getPluginLatestVersion($plugin)
    {
        $pluginDir = PLUGIN_DIR . '/' . $plugin->name;

        if (file_exists("$pluginDir/.git")) {
            return $this->getGitPluginLatestVersion($plugin);
        } else {
            $repository = new OmekaDotOrgRepository();
            $pluginInfo = $repository->find($plugin->name);
            if ($pluginInfo) {
                return $pluginInfo['version'];
            }
        }
    }

    public function getGitPluginLatestVersion($plugin)
    {
        // TODO: Move github specific code to GitHub repo class.
        $pluginDir = escapeshellarg(PLUGIN_DIR . '/' . $plugin->name);
        $currentBranch = rtrim(`git -C $pluginDir rev-parse --abbrev-ref HEAD`);
        $remoteName = rtrim(`git -C $pluginDir config branch.$currentBranch.remote`);
        if (empty($remoteName)) {
            $this->logger->warning('{plugin} was downloaded using Git but the current branch ({branch}) has no upstream. Trying origin', array('plugin' => $plugin->name, 'branch' => $currentBranch));
            $remoteName = 'origin';
        }

        $remoteTag = rtrim(`git -C $pluginDir fetch -q $remoteName && git -C $pluginDir describe --tags --abbrev=0 @{u}`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (empty($remoteVersion)) {
            $this->logger->warning('{plugin}: remote {remote} has no tags', array('plugin' => $plugin->name, 'remote' => $remoteName));

            return null;
        }

        return $remoteVersion;
    }

    public function update($plugin)
    {
        $pluginDir = PLUGIN_DIR . '/' . $plugin->name;
        if (file_exists("$pluginDir/.git")) {
            $pluginDirQuoted = escapeshellarg($pluginDir);
            $remoteTag = rtrim(`git -C $pluginDirQuoted describe --tags --abbrev=0 @{u}`);
            exec("git -C $pluginDirQuoted rebase $remoteTag", $output, $exitCode);
            if ($exitCode) {
                $this->logger->error('Cannot update {plugin}', array('plugin' => $plugin->name));

                return false;
            }
        } else {
            $backupDir = getenv('HOME') . '/.omeka-cli/backups';
            if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true)) {
                $this->logger->error('Cannot create backup directory ({dir}). Plugin {plugin} will not be updated.', array('dir' => $backupDir, 'plugin' => $plugin->name));

                return false;
            }

            try {
                $repo = new OmekaDotOrgRepository();
                $pluginInfo = $repo->find($plugin->name);
                $tmpDir = $repo->download($pluginInfo, null);

                if (false === rename($pluginDir, sprintf('%s/%s_%s', $backupDir, $plugin->name, date('YmdHis')))) {
                    throw new \Exception('Cannot backup plugin directory');
                }
                if (false === rename($tmpDir, $pluginDir)) {
                    throw new \Exception('Cannot move the newly downloaded plugin to its destination');
                }
            } catch (\Exception $e) {
                $this->logger->error('cannot update plugin : {message}', array('message' => $e->getMessage()));

                return false;
            }
        }

        return true;
    }
}
