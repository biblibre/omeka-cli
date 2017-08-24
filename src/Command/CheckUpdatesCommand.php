<?php

namespace OmekaCli\Command;

use OmekaCli\Application;
use OmekaCli\Command\PluginCommands\Update;

class CheckUpdatesCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'tell if omeka-cli, Omeka and its plugins are up-to-date';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    check-updates|chup' . PHP_EOL
               . PHP_EOL
               . 'Tell if omeka-cli, Omeka and its plugins are up-to-date.'
               . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (file_exists(OMEKACLI_PATH . '/.git')) {
            $output = shell_exec('git -C ' . OMEKACLI_PATH . ' log --oneline HEAD..@{u}');
            if (empty($output))
                $this->logger->info('omeka-cli is up-to-date.');
            else
                echo 'omeka-cli' . PHP_EOL;
        } else {
            $remoteVersion = shell_exec('git -C ' . OMEKACLI_PATH . ' ls-remote --tags https://github.com/biblibre/omeka-cli 2>/dev/null | grep -o \'[0-9]\+\.[0-9]\+\.[0-9]\+\' | sort -rV | sed 1q');
            if (OMEKACLI_VERSION == $remoteVersion)
                $this->logger->info('omeka-cli is up-to-date.');
            else
                echo 'omeka-cli' . PHP_EOL;
        }

        if (!$application->isOmekaInitialized()) {
            $this->logger->error('Omeka is not initialized here.');
            return 1;
        }

        $db = get_db();
        $pluginsTable = $db->getTable('Plugin');
        $activePlugins = $pluginsTable->findBy(array('active' => 1));
        $inactivePlugins = $pluginsTable->findBy(array('active' => 0));

        if (version_compare(OMEKA_VERSION, latest_omeka_version() ) >= 0)
            $this->logger->info('Omeka is up-to-date.');
        else
            echo 'Omeka' . PHP_EOL;

        if (OMEKA_VERSION != get_option('omeka_version'))
            $this->logger->warning('Omeka version and database version do not match!');

        $this->logger->info('Plugins status:');
        $updateCommand = new Update();
        $updateCommand->setLogger($this->logger);
        return $updateCommand->run(array('list' => true), array(), $application);
    }
}
