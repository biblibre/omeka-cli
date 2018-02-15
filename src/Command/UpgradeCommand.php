<?php

namespace OmekaCli\Command;

use Omeka_Db_Migration_Manager;
use OmekaCli\Omeka;
use OmekaCli\Sandbox\OmekaSandbox;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpgradeCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('upgrade');
        $this->setDescription('upgrade Omeka');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $stderr->writeln('Error: Not in an Omeka directory');

            return 1;
        }

        $omeka = $this->getOmeka();
        if (!file_exists($omeka->BASE_DIR . '/.git')) {
            $stderr->writeln('Error: omeka-cli needs a git repo to upgrade Omeka');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Checking for updates');
        }

        $latestVersion = $this->getLatestVersion();
        if (version_compare($omeka->OMEKA_VERSION, $latestVersion) >= 0) {
            $stderr->writeln('Omeka is already up-to-date');

            return 0;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Saving database');
        }

        if (false === $this->saveDb()) {
            $stderr->writeln('Error: Failed to dump database');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Upgrading Omeka');
        }

        if (false === $this->upgradeOmeka($latestVersion)) {
            $stderr->writeln('Error: Cannot upgrade Omeka');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Upgrading database');
        }

        if (false === $this->upgradeDb($latestVersion)) {
            $stderr->writeln('Error: Failed to upgrade database');

            return 1;
        }

        $stderr->writeln('Upgrade successful');

        return 0;
    }

    protected function getLatestVersion()
    {
        $latestTag = rtrim(`git ls-remote -q --tags --refs https://github.com/omeka/Omeka 'v*' | cut -f 2 | sed 's|refs/tags/||' | sort -rV | head -n1`);
        $latestVersion = ltrim($latestTag, 'v');

        return $latestVersion;
    }

    protected function saveDb()
    {
        $stderr = $this->getStderr();

        $backupsDir = getenv('HOME') . '/.omeka-cli/backups';
        if (!is_dir($backupsDir)) {
            mkdir($backupsDir, 0777, true);
        }

        $db = parse_ini_file($this->getOmeka()->BASE_DIR . '/db.ini');
        $dest = sprintf('%s/sql/%s-%s.sql.gz', $backupsDir, $db['dbname'], date('YmdHis'));
        if (!is_dir(dirname($dest))) {
            mkdir(dirname($dest), 0777, true);
        }

        $passwordFile = tempnam(sys_get_temp_dir(), 'omeka.cnf.');
        file_put_contents($passwordFile, '[client]' . PHP_EOL . "password = {$db['password']}");
        $mysqldump_cmd = 'mysqldump'
            . ' --defaults-file=' . escapeshellarg($passwordFile)
            . ' --host=' . escapeshellarg($db['host'])
            . ' --user=' . escapeshellarg($db['username'])
            . ' ' . escapeshellarg($db['dbname'])
            . ' | gzip -c > ' . escapeshellarg($dest);

        exec($mysqldump_cmd, $out, $exitCode);

        unlink($passwordFile);

        if ($exitCode !== 0) {
            $stderr->writeln('Error: mysqldump failed');

            return false;
        }

        $stderr->writeln(sprintf('Database saved in %s', $dest));

        return true;
    }

    protected function upgradeOmeka($version)
    {
        $baseDir = escapeshellarg($this->getOmeka()->BASE_DIR);
        exec("git -C $baseDir stash save -q 'Stash made by omeka-cli upgrade'");
        exec("git -C $baseDir fetch -q origin");
        exec("git -C $baseDir reset --hard -q v$version", $out, $ans);

        return $ans === 0;
    }

    protected function upgradeDb()
    {
        $stderr = $this->getStderr();

        try {
            $this->getSandbox()->execute(function () {
                $migrationMgr = Omeka_Db_Migration_Manager::getDefault();
                if ($migrationMgr->dbNeedsUpgrade()) {
                    $migrationMgr->migrate();
                    $migrationMgr->finalizeDbUpgrade();
                }
            }, OmekaSandbox::ENV_SHORTLIVED);
        } catch (\Exception $e) {
            $stderr->writeln(sprintf('Error: Database migration failed: %s', $e->getMessage()));

            return false;
        }

        return true;
    }
}
