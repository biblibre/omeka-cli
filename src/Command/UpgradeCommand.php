<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\UIUtils;

class UpgradeCommand extends AbstractCommand
{
    protected $plugins;

    public function getDescription()
    {
        return 'upgrade Omeka';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    upgrade' . PHP_EOL
               . PHP_EOL
               . 'All saves are put in the ~/.omeka-cli/backups directory.'
               . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (!$application->isOmekaInitialized()) {
            $this->logger->error('not in an Omeka directory');
            return 1;
        }

        if (!is_dir(BASE_DIR . '/.git')) {
            $this->logger->Error('omeka-cli needs a git repo to upgrade Omeka');
            return 1;
        }

        $this->logger->info('checking for updates');
        $lastVersion = $this->getNewVersion();
        if (!$lastVersion) {
            $this->logger->error('Omeka is already up-to-date');
            return 1;
        }

        $this->logger->info('saving database');
        if ($this->backupDb()) {
            $this->logger->error('database dumping failed');
            return 1;
        }

        $this->logger->info('deactivating plugins');
        $this->deactivatePlugins();

        $this->logger->info('saving Omeka');
        if ($this->saveOmeka()) {
            $this->logger->error('cannot save Omeka');
            return 1;
        }

        $this->logger->info('upgrading Omeka');
        if ($this->upgradeOmeka($lastVersion)) {
            $this->logger->error('cannot upgrade Omeka');
            $this->logger->info('recovering Omeka and its database');
            if ($this->recover())
                $this->logger->error('cannot recover Omeka or its database');
            else
                $this->logger->info('recovery successful');
            return 1;
        }

        $this->logger->info('upgrading database');
        $this->upgradeDb($lastVersion);

        $this->logger->info('upgrade successful');

        return 0;
    }

    protected function getNewVersion()
    {
        $lastVersion = shell_exec('git ls-remote --tags | grep -ho \'v[0-9]\+\(\.[0-9]\+\)*\' | cut -dv -f2 | tail -n1');
        $lastVersion = trim($lastVersion);

        return version_compare(OMEKA_VERSION, $lastVersion) < 0 ? $lastVersion : null;
    }

    protected function backupDb()
    {
        if (!is_dir(getenv('HOME') . '/.omeka-cli/backups')) {
            if (!is_dir(getenv('HOME') . '/.omeka-cli'))
                mkdir(getenv('HOME') . '/.omeka-cli');
            mkdir(getenv('HOME') . '/.omeka-cli/backups');
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

        exec('mysqldump -h\'' . $infos['host']     . '\''
           . ' -u\'' .          $infos['username'] . '\''
           . ' -p\'' .          $infos['password'] . '\''
           . ' \'' .            $infos['dbname']   . '\''
           . ' > ~/.omeka-cli/backups/omeka_db_backup.sql', $out, $ans);

        return $ans;
    }

    protected function deactivatePlugins()
    {
        $this->plugins = get_db()->getTable('Plugin')->findBy(array('active' => 1));
        foreach ($this->plugins as $plugin) {
            $broker = $plugin->getPluginBroker();
            $loader = new \Omeka_Plugin_Loader($broker,
                                               new \Omeka_Plugin_Ini(PLUGIN_DIR),
                                               new \Omeka_Plugin_Mvc(PLUGIN_DIR),
                                               PLUGIN_DIR);
            $installer = new \Omeka_Plugin_Installer($broker, $loader);
            $installer->deactivate($plugin);
        }
    }

    protected function saveOmeka()
    {
        $dir = opendir(BASE_DIR); 
        if (!is_dir(getenv('HOME') . '/.omeka-cli/backups')) {
            if (!is_dir(getenv('HOME') . '/.omeka-cli'))
                mkdir(getenv('HOME') . '/.omeka-cli');
            mkdir(getenv('HOME') . '/.omeka-cli/backups');
        }
        exec('cp -fr ' . BASE_DIR . ' ' . getenv('HOME') . '/.omeka-cli/backups/Omeka', $out, $ans);

        return $ans;
    }

    protected function upgradeOmeka($tag)
    {
        exec('git pull --rebase origin v' . $tag, $out, $ans);

        return $ans;
    }

    protected function upgradeDb($ver)
    {
        $migrationMgr = new \Omeka_Db_Migration_Manager(get_db(), UPGRADE_DIR);
        if ($migrationMgr->canUpgrade()) {
            $migrationMgr->migrate();
            set_option('omeka_version', $ver);
        }
    }

    protected function recover()
    {
        if (!is_dir(getenv('HOME') . '/.omeka-cli/backups')) {
            if (!is_dir(getenv('HOME') . '/.omeka-cli'))
                mkdir(getenv('HOME') . '/.omeka-cli');
            mkdir(getenv('HOME') . '/.omeka-cli/backups');
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

        exec('cp -fr ' . getenv('HOME') . '/.omeka-cli/backups/Omeka'. ' ' . BASE_DIR , $out, $ans1);
        exec('mysqldump -h\'' . $infos['host']     . '\''
           . ' -u\'' .          $infos['username'] . '\''
           . ' -p\'' .          $infos['password'] . '\''
           . ' \'' .            $infos['dbname']   . '\''
           . ' < ~/.omeka-cli/backups/omeka_db_backup.sql', $out, $ans2);

        return $ans1 | $ans2;
    }
}
