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
               . '    snapshot c[reate]' . PHP_EOL
               . '    snapshot r[ecover] [-c] SRC DST)' . PHP_EOL
               . PHP_EOL
               . 'Create/recover a snapshot of the current Omeka '
               . 'installation.' . PHP_EOL
               . 'When recovering, use the -c option to reconfigure '
               . 'db.ini file.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($args)) {
            echo $this->getUsage();

            return 1;
        } elseif (strpos('create', $args[0]) == 0) {
            $action = array_pop($args);
        } elseif (strpos('recover', $args[0]) == 0) {
            $dst = array_pop($args);
            $src = array_pop($args);
            $reconf = count($args) == 2 && array_pop($args) == '-c';
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
            $ans = $this->recover($reconf, $src, $dst);
            break;
        default:
            $this->logger->error('unknown action');
            echo $this->getUsage();

            return 1;
        }

        return $ans;
    }

    protected function create()
    {
        if (!is_dir(getenv('HOME') . '/.omeka-cli/snapshots')) {
            if (!is_dir(getenv('HOME') . '/.omeka-cli')) {
                mkdir(getenv('HOME') . '/.omeka-cli');
            }
            mkdir(getenv('HOME') . '/.omeka-cli/snapshots');
        }
        $snapPath = getenv('HOME') . '/.omeka-cli/snapshots/snapshot_' . date('YmdHi');
        if (!is_dir($snapPath)) {
            mkdir($snapPath);
        }

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
        exec('mysqldump -h\'' . $infos['host'] . '\''
           . ' -u\'' . $infos['username'] . '\''
           . ' -p\'' . $infos['password'] . '\''
           . ' \'' . $infos['dbname'] . '\''
           . ' | gzip > ' . $snapPath . '/omeka_db_backup.sql.gz', $out, $ans);
        if ($ans) {
            $this->logger->error('database compression failed');

            return 1;
        }

        $this->logger->info('saving Omeka');
        exec('tar czf ' . $snapPath . '/Omeka.tar.gz  -C ' . BASE_DIR . ' .', $out, $ans);
        if ($ans) {
            $this->logger->error('Omeka compression failed');

            return 1;
        }

        $this->logger->info('archiving');
        exec('tar cvf ' . $snapPath . '.tar -C ' . $snapPath . ' .', $out, $ans);
        if ($ans) {
            $this->logger->error('snapshot archiving failed');

            return 1;
        }
        exec('rm -rf ' . $snapPath, $out, $ans);
        if ($ans) {
            $this->logger->warning('cannot remove non-archived directory');

            return 1;
        }

        $this->logger->info('snapshot created: ' . $snapPath . '.tar');

        return 0;
    }

    protected function recover($reconf, $src, $dst)
    {
        $this->logger->info('checking destination');
        if (!is_dir($dst)) {
            if (file_exists($dst)) {
                $this->logger->error($dst . ' already exists and is not a directory');

                return 1;
            }
            if (!mkdir($dst)) {
                $this->logger->error('cannot create ' . $dst . 'directory');

                return 1;
            }
        }

        $this->logger->info('recovering Omeka');
        exec('tar xf ' . $src . ' -O ./Omeka.tar.gz | '
           . 'tar xzf - -C ' . $dst, $out, $ans);
        if ($ans) {
            $this->logger->error('Omeka recovering failed');

            return 1;
        }

        $this->logger->info('looking for infos in db.ini');
        if ($reconf) {
            if ($this->configDb($dst)) {
                return 1;
            }
        }
        $lines = file($dst . '/db.ini', FILE_IGNORE_NEW_LINES);
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

        $this->logger->info('recovering database');
        exec('tar xf ' . $src . ' -O ./omeka_db_backup.sql.gz | '
           . 'gzip -c -d | '
           . 'mysql -h\'' . $infos['host'] . '\''
           . ' -u\'' . $infos['username'] . '\''
           . ' -p\'' . $infos['password'] . '\''
           . ' \'' . $infos['dbname'] . '\'', $out, $ans);
        if ($ans) {
            $this->logger->error('database recovering failed');

            return 1;
        }

        $this->logger->info('recovering successful');

        return 0;
    }

    protected function configDb($dir)
    {
        $dbini = $dir . '/db.ini';
        if (!file_exists($dbini)) {
            $this->logger->error('db.ini file not found');

            return 1;
        }
        do {
            echo 'host: ';
            $host = trim(fgets(STDIN));
            echo 'username: ';
            $username = trim(fgets(STDIN));
            echo 'password: ';
            $password = trim(fgets(STDIN));
            echo 'dbname: ';
            $dbname = trim(fgets(STDIN));

            echo PHP_EOL;
            echo 'host:     ' . $host . PHP_EOL;
            echo 'username: ' . $username . PHP_EOL;
            echo 'password: ' . $password . PHP_EOL;
            echo 'dbname:   ' . $dbname . PHP_EOL;
        } while (!UIUtils::confirmPrompt('Are those informations correct?'));
        exec('sed -i \'s/host     = ".*"/host     = "' . $host . '"/\' ' . $dbini);
        exec('sed -i \'s/username = ".*"/username = "' . $username . '"/\' ' . $dbini);
        exec('sed -i \'s/password = ".*"/password = "' . $password . '"/\' ' . $dbini);
        exec('sed -i \'s/dbname   = ".*"/dbname   = "' . $dbname . '"/\' ' . $dbini);

        return 0;
    }
}
