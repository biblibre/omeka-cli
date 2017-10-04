<?php

namespace OmekaCli\Command;

class SnapshotCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'create a snapshot of the current Omeka installation';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
               . "\tsnapshot\n"
               . "\tsnap\n";

        return $usage;
    }

    public function run($options, $args)
    {
        if (!empty($args)) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

            return 1;
        }

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $this->logger->error('Not in an Omeka directory');

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

        $this->logger->info('Saving database');
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
            $this->logger->error('database dump failed');

            return 1;
        }

        $this->logger->info('saving Omeka');
        $omekaTarFile = $snapPath . '/Omeka.tar.gz';
        exec('tar czf ' . escapeshellarg($omekaTarFile)
            . ' -C ' . escapeshellarg($this->getOmeka()->BASE_DIR) . ' .', $out, $exitCode);

        if ($exitCode) {
            $this->logger->error('Omeka compression failed');

            return 1;
        }

        $this->logger->info('archiving');
        $tarFile = "$snapPath.tar";
        exec('tar cvf ' . escapeshellarg($tarFile)
            . ' -C ' . escapeshellarg($snapPath) . ' .', $out, $exitCode);

        if ($exitCode) {
            $this->logger->error('snapshot archiving failed');

            return 1;
        }

        exec('rm -rf ' . escapeshellarg($snapPath), $out, $exitCode);
        if ($exitCode) {
            $this->logger->warning('cannot remove non-archived directory');

            return 1;
        }

        $this->logger->notice('Snapshot created at {file}', array('file' => $tarFile));

        return 0;
    }
}
