<?php

namespace OmekaCli\Command;

use GetOptionKit\OptionCollection;
use OmekaCli\IniWriter;

class SnapshotRestoreCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'restore a snapshot previously made with command snapshot';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\tsnapshot-restore [--db-host <hostname>] [--db-user <user>]\n"
            . "\t                 [--db-pass <password>] [--db-name <name>]\n"
            . "\t                 <snapshot> <target>\n";

        return $usage;
    }

    public function getOptionsSpec()
    {
        $optionsSpec = new OptionCollection();
        $optionsSpec->add('h|db-host:', 'database host')
                ->isa('String');
        $optionsSpec->add('u|db-user:', 'database user name')
                ->isa('String');
        $optionsSpec->add('p|db-pass:', 'database user password')
                ->isa('String');
        $optionsSpec->add('n|db-name:', 'database name')
                ->isa('String');

        return $optionsSpec;
    }

    public function run($options, $args)
    {
        if (count($args) != 2) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $snapshot = $args[0];
        $target = $args[1];

        if (!file_exists($snapshot)) {
            $this->logger->error('{snapshot} does not exist', array('snapshot' => $snapshot));

            return 1;
        }

        $this->logger->info('checking destination');
        if (!is_dir($target)) {
            if (file_exists($target)) {
                $this->logger->error('{target} already exists and is not a directory', array('target' => $target));

                return 1;
            }

            if (!mkdir($target, 0777, true)) {
                $this->logger->error('cannot create {target} directory', array('target' => $target));

                return 1;
            }
        }

        $this->logger->info('restoring Omeka');
        exec('tar xf ' . escapeshellarg($snapshot) . ' -O ./Omeka.tar.gz | '
           . 'tar xzf - -C ' . escapeshellarg($target), $out, $exitCode);
        if ($exitCode) {
            $this->logger->error('Omeka restoration failed');

            return 1;
        }

        $this->logger->info('looking for infos in db.ini');
        $dbIniFile = "$target/db.ini";
        $db = parse_ini_file($dbIniFile, true);

        $confMap = array(
            'db-host' => 'host',
            'db-user' => 'username',
            'db-pass' => 'password',
            'db-name' => 'dbname',
        );

        $dbHasChanged = false;
        foreach ($confMap as $optionKey => $dbIniKey) {
            if (isset($options[$optionKey])) {
                $db['database'][$dbIniKey] = $options[$optionKey];
                $dbHasChanged = true;
            }
        }

        if ($dbHasChanged) {
            $iniWriter = new IniWriter($dbIniFile);
            $iniWriter->writeArray($db);
        }

        $this->logger->info('Restoring database');
        $passwordFile = tempnam(sys_get_temp_dir(), 'omeka.cnf.');
        file_put_contents($passwordFile, '[client]' . PHP_EOL . "password = {$db['database']['password']}");

        exec('tar xf ' . escapeshellarg($snapshot) . ' -O ./omeka.sql.gz'
            . ' | gzip -cd | '
            . 'mysql'
            . ' --defaults-file=' . escapeshellarg($passwordFile)
            . ' --host=' . escapeshellarg($db['database']['host'])
            . ' --user=' . escapeshellarg($db['database']['username'])
            . ' ' . escapeshellarg($db['database']['dbname']), $out, $exitCode);

        unlink($passwordFile);

        if ($exitCode) {
            $this->logger->error('database recovering failed');

            return 1;
        }

        $this->logger->notice('Snapshot restored successfully');

        return 0;
    }
}
