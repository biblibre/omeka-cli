<?php

namespace OmekaCli\Command;

use OmekaCli\IniWriter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotRestoreCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('snapshot-restore');
        $this->setDescription('restore a snapshot previously made with command snapshot');

        $this->addOption('db-host', null, InputOption::VALUE_OPTIONAL, 'database host');
        $this->addOption('db-user', null, InputOption::VALUE_OPTIONAL, 'database user');
        $this->addOption('db-pass', null, InputOption::VALUE_OPTIONAL, 'database pass');
        $this->addOption('db-name', null, InputOption::VALUE_OPTIONAL, 'database name');

        $this->addArgument('snapshot', InputArgument::REQUIRED, 'snapshot file created by snapshot command');
        $this->addArgument('target', InputArgument::REQUIRED, 'destination directory');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $snapshot = $input->getArgument('snapshot');
        $target = $input->getArgument('target');
        $stderr = $this->getStderr();

        if (!file_exists($snapshot)) {
            $stderr->writeln(sprintf('Error: %s does not exist', $snapshot));

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Checking destination');
        }

        if (!is_dir($target)) {
            if (file_exists($target)) {
                $stderr->writeln(sprintf('Error: %s already exists and is not a directory', $target));

                return 1;
            }

            if (!mkdir($target, 0777, true)) {
                $stderr->writeln(sprintf('Error: Cannot create %s directory', $target));

                return 1;
            }
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Restoring Omeka');
        }

        exec('tar xf ' . escapeshellarg($snapshot) . ' -O ./Omeka.tar.gz | '
           . 'tar xzf - -C ' . escapeshellarg($target), $out, $exitCode);
        if ($exitCode) {
            $stderr->writeln('Error: Omeka restoration failed');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Looking for infos in db.ini');
        }

        $dbIniFile = "$target/db.ini";
        $db = parse_ini_file($dbIniFile, true);

        $confMap = [
            'db-host' => 'host',
            'db-user' => 'username',
            'db-pass' => 'password',
            'db-name' => 'dbname',
        ];

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

        if ($stderr->isVerbose()) {
            $stderr->writeln('Restoring database');
        }

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
            $stderr->writeln('Error: Database recovering failed');

            return 1;
        }

        $stderr->writeln('Snapshot restored successfully');

        return 0;
    }
}
