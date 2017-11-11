<?php

namespace OmekaCli\Plugin;

use OmekaCli\Context\Context;
use OmekaCli\Context\ContextAwareInterface;
use OmekaCli\Context\ContextAwareTrait;
use OmekaCli\Omeka;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Updater implements ContextAwareInterface
{
    use ContextAwareTrait;

    protected $output;

    public function __construct()
    {
        $this->setContext(new Context());
        $this->output = new NullOutput();
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
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
            $this->output->writeln(sprintf('Warning: %1$s was downloaded using Git but the current branch (%2$s) has no upstream', $pluginName, $currentBranch));

            return null;
        }

        $remoteTag = rtrim(`git -C $pluginDir fetch -q $remoteName && git -C $pluginDir describe --tags --abbrev=0 @{u} 2>/dev/null`);
        $remoteVersion = ltrim($remoteTag, 'v');
        if (empty($remoteVersion)) {
            $this->output->writeln(sprintf('Warning: %1$s: remote %2$s has no tags', $pluginName, $remoteName));

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
                $this->output->writeln(sprintf('Error: Cannot update %s', $pluginName));

                return false;
            }
        } else {
            $backupDir = getenv('HOME') . '/.omeka-cli/backups';
            if (!is_dir($backupDir) && !mkdir($backupDir, 0777, true)) {
                $this->output->writeln(sprintf('Error: Cannot create backup directory (%1$s). Plugin %2$s will not be updated.', $backupDir, $pluginName));

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
                $this->output->writeln(sprintf('Error: Cannot update plugin : %s', $e->getMessage()));

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
