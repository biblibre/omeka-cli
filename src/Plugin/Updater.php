<?php

namespace OmekaCli\Plugin;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use OmekaCli\Context\Context;
use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;
use OmekaCli\Omeka;

class Updater implements LoggerAwareInterface, ContextAwareInterface
{
    use LoggerAwareTrait;
    use ContextAwareTrait;

    public function __construct()
    {
        $this->setLogger(new NullLogger());
        $this->setContext(new Context());
    }

    public function getPluginLatestVersion($pluginName)
    {
        $pluginDir = $this->getPluginDir($pluginName);

        if (file_exists("$pluginDir/.git")) {
            return $this->getGitPluginLatestVersion($pluginName);
        } else {
            $repository = new OmekaDotOrgRepository();
            $pluginInfo = $repository->find($pluginName);
            if (!empty($pluginInfo)) {
                return $pluginInfo['version'];
            }
        }
    }

    public function getGitPluginLatestVersion($pluginName)
    {
        // TODO: Move github specific code to GitHub repo class.
        $pluginDir = escapeshellarg($this->getPluginDir($pluginName));
        $currentBranch = rtrim(`git -C $pluginDir rev-parse --abbrev-ref HEAD`);
        $remoteName = rtrim(`git -C $pluginDir config branch.$currentBranch.remote`);
        if (empty($remoteName)) {
            $this->logger->warning('{plugin} was downloaded using Git but the current branch ({branch}) has no upstream', array('plugin' => $pluginName, 'branch' => $currentBranch));

            return null;
        }

        $remoteTag = rtrim(`git -C $pluginDir fetch -q $remoteName && git -C $pluginDir describe --tags --abbrev=0 @{u} 2>/dev/null`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (empty($remoteVersion)) {
            $this->logger->warning('{plugin}: remote {remote} has no tags', array('plugin' => $pluginName, 'remote' => $remoteName));

            return null;
        }

        return $remoteVersion;
    }

    public function update($pluginName)
    {
        $pluginDir = $this->getPluginDir($pluginName);

        if (file_exists("$pluginDir/.git")) {
            $pluginDirQuoted = escapeshellarg($pluginDir);
            $remoteTag = rtrim(`git -C $pluginDirQuoted describe --tags --abbrev=0 @{u}`);
            exec("git -C $pluginDirQuoted rebase $remoteTag", $output, $exitCode);
            if ($exitCode) {
                $this->logger->error('Cannot update {plugin}', array('plugin' => $pluginName));

                return false;
            }
        } else {
            $backupDir = getenv('HOME') . '/.omeka-cli/backups';
            if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true)) {
                $this->logger->error('Cannot create backup directory ({dir}). Plugin {plugin} will not be updated.', array('dir' => $backupDir, 'plugin' => $pluginName));

                return false;
            }

            try {
                $repo = new OmekaDotOrgRepository();
                $pluginInfo = $repo->find($pluginName);
                $tmpDir = $repo->download($pluginInfo['id'], null);

                if (false === rename($pluginDir, sprintf('%s/%s_%s', $backupDir, $pluginName, date('YmdHis')))) {
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

    protected function getOmeka()
    {
        if (!isset($this->omeka)) {
            $this->omeka = new Omeka();
            $this->omeka->setContext($this->getContext());
        }

        return $this->omeka;
    }

    protected function getPluginDir($pluginName)
    {
        $omeka = $this->getOmeka();

        return $omeka->PLUGIN_DIR . '/' . $pluginName;
    }
}
