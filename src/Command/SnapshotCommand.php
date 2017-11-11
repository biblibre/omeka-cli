<?php

namespace OmekaCli\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SnapshotCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('snapshot');
        $this->setAliases(array('snap'));
        $this->setDescription('create a snapshot of the current Omeka installation');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $stderr->writeln('Error: Not in an Omeka directory');

            return 1;
        }

        $snapshotsDir = getenv('HOME') . '/.omeka-cli/snapshots';
        if (!is_dir($snapshotsDir)) {
            mkdir($snapshotsDir, 0777, true);
        }

        $snapPath = sprintf('%s/%s', $snapshotsDir, date('YmdHis'));

        if (!is_dir($snapPath)) {
            mkdir($snapPath, 0777, true);
        }

        $db = parse_ini_file($this->getOmeka()->BASE_DIR . '/db.ini');

        if ($stderr->isVerbose()) {
            $stderr->writeln('Saving database');
        }

        $dbDumpFile = $snapPath . '/omeka.sql.gz';
        $passwordFile = tempnam(sys_get_temp_dir(), 'omeka.cnf.');
        file_put_contents($passwordFile, '[client]' . PHP_EOL . "password = {$db['password']}");
        exec('mysqldump'
            . ' --defaults-file=' . escapeshellarg($passwordFile)
            . ' --host=' . escapeshellarg($db['host'])
            . ' --user=' . escapeshellarg($db['username'])
            . ' ' . escapeshellarg($db['dbname'])
            . ' | gzip > ' . escapeshellarg($dbDumpFile), $out, $exitCode);

        unlink($passwordFile);

        if ($exitCode) {
            $stderr->writeln('Error: Database dump failed');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Saving Omeka');
        }

        $omekaTarFile = $snapPath . '/Omeka.tar.gz';
        exec('tar czf ' . escapeshellarg($omekaTarFile)
            . ' -C ' . escapeshellarg($this->getOmeka()->BASE_DIR) . ' .', $out, $exitCode);

        if ($exitCode) {
            $stderr->writeln('Error: Omeka compression failed');

            return 1;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Archiving');
        }

        $tarFile = "$snapPath.tar";
        exec('tar cvf ' . escapeshellarg($tarFile)
            . ' -C ' . escapeshellarg($snapPath) . ' .', $out, $exitCode);

        if ($exitCode) {
            $stderr->writeln('Error: Snapshot archiving failed');

            return 1;
        }

        exec('rm -rf ' . escapeshellarg($snapPath), $out, $exitCode);
        if ($exitCode) {
            $stderr->writeln('Error: Cannot remove non-archived directory');

            return 1;
        }

        $stderr->writeln(sprintf('Snapshot created at %s', $tarFile));

        return 0;
    }
}
