<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

class SnapshotCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'create a snapshot of the current Omeka installation';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    snapshot c[reate]|r[ecover]' . PHP_EOL
               . PHP_EOL
               . 'Create/recover a snapshot of the current Omeka '
               . 'installation.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            $action = 'c';
        } elseif (count($args) == 1) {
            $action = array_pop($args);
        } else {
            echo $this->getUsage();
            return 1;
        }

        switch ($action) {
        case 'c':
        case 'create':
            $ans = $this->create();
            break;
        case 'r':
        case 'recover':
            $ans = $this->recover();
            break;
        default:
            $this->logger->error('unknown action');
            echo $this->getUsage();
            return 1;
        }
        $this->snapDir = getenv('HOME') . '/.omeka-cli/snapshots';

        return $ans;
    }

    protected function create()
    {
        if (!is_dir(getenv('HOME') . '/.omeka-cli/snapshots')) {
            if (!is_dir(getenv('HOME') . '/.omeka-cli'))
                mkdir(getenv('HOME') . '/.omeka-cli');
            mkdir(getenv('HOME') . '/.omeka-cli/snapshots');
        }
        $snapPath = getenv('HOME') . '/.omeka-cli/snapshots/snapshot_' . date('YmdHi');
        mkdir($snapPath);

        $lines = file(BASE_DIR . '/db.ini', FILE_IGNORE_NEW_LINES);
        $lines = array_filter(
            $lines,
            function ($var) {
                return preg_match('/(host|username|password|dbname)/', $var);
            }
        );
        foreach ($lines as $line) {
            $line = str_replace(' ', '', $line);
            $line = str_replace('"', '', $line);
            $line = explode('=', $line);
            $infos[$line[0]] = $line[1];
        }

        $this->logger->info('saving database');
        exec('mysqldump -h\'' . $infos['host']     . '\''
           . ' -u\'' .          $infos['username'] . '\''
           . ' -p\'' .          $infos['password'] . '\''
           . ' \'' .            $infos['dbname']   . '\''
           . ' | gzip > ' . $snapPath . '/omeka_db_backup.sql.gz', $out, $ans);
        if ($ans) {
            $this->logger->error('something bad occured');
            return 1;
        }
        $this->logger->info('saving Omeka');
        exec('cp -fr ' . BASE_DIR . ' ' . $snapPath . '/Omeka', $out, $ans);
        if ($ans) {
            $this->logger->error('something bad occured');
            return 1;
        }

        return 0;
    }

    protected function recover()
    {
        if (!is_dir(getenv('HOME') . '/.omeka-cli/snapshots')) {
            $this->logger->error('no snapshots directory found');
            return 1;
        }
        if (count(scandir(getenv('HOME') . '/.omeka-cli/snapshots')) == 2) {
            $this->logger->error('no snapshot found');
            return 1;
        }
        $snapsPath = getenv('HOME') . '/.omeka-cli/snapshots';

        $lines = file(BASE_DIR . '/db.ini', FILE_IGNORE_NEW_LINES);
        $lines = array_filter(
            $lines,
            function ($var) {
                return preg_match('/(host|username|password|dbname)/', $var);
            }
        );
        foreach ($lines as $line) {
            $line = str_replace(' ', '', $line);
            $line = str_replace('"', '', $line);
            $line = explode('=', $line);
            $infos[$line[0]] = $line[1];
        }

        $snaps = scandir($snapsPath);
        $snaps = array_filter(
            $snaps,
            function($v) {
                return in_array($v, array('.', '..')) ? null : $v;
            }
        );
        $snaps = array_values($snaps);
        asort($snaps);
        $chosen = UIUtils::menuPrompt('Chose a snapshot', $snaps);
        if ($chosen < 0) {
            $this->logger->info('nothing chosen, aborting');
            return 0;
        }
        $snapPath = $snapsPath . '/' . $snaps[$chosen];
        exec('cp -frT ' . $snapPath . '/Omeka ' . BASE_DIR, $out, $ans);
        if ($ans) {
            $this->logger->error('something bad occured');
            return 1;
        }
        exec('gzip -d -c ' . $snapPath . '/omeka_db_backup.sql.gz'
           . ' | mysql -h\'' .     $infos['host']     . '\''
           . ' -u\'' .          $infos['username'] . '\''
           . ' -p\'' .          $infos['password'] . '\''
           . ' \'' .            $infos['dbname']   . '\'', $out, $ans);
        if ($ans) {
            $this->logger->error('something bad occured');
            return 1;
        }

        return 0;
    }
}
