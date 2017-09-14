<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

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

    public function run($options, $args, Application $application)
    {
        if (!empty($args)) {
            $this->logger->error('Bad number of arguments');
            error_log($this->getUsage());

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

        $db = parse_ini_file(BASE_DIR . '/db.ini');

        $this->logger->info('saving database');
        $dbDumpFile = $snapPath . '/omeka_db_backup.sql.gz';
        exec('mysqldump --host=' . escapeshellarg($db['host'])
           . ' --user=' . escapeshellarg($db['username'])
           . ' --password=' . escapeshellarg($db['password'])
           . ' ' . escapeshellarg($db['dbname'])
           . ' | gzip > ' . escapeshellarg($dbDumpFile), $out, $exitCode);

        if ($exitCode) {
            $this->logger->error('database dump failed');

            return 1;
        }

        $this->logger->info('saving Omeka');
        $omekaTarFile = $snapPath . '/Omeka.tar.gz';
        exec('tar czf ' . escapeshellarg($omekaTarFile)
            . ' -C ' . escapeshellarg(BASE_DIR) . ' .', $out, $exitCode);

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
