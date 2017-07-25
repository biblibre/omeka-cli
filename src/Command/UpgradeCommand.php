<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

use Github\Client;
use Github\Exception\RuntimeException;

class UpgradeCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'check omeka-cli and Omeka version';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    upgrade' . PHP_EOL
               . PHP_EOL
               . 'Check omeka-cli and Omeka version.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (!empty($options) || !empty($args)) {
            echo 'Error: wrong usage.' . PHP_EOL;
            echo $this->getUsage();
            return 1;
        }

        if (file_exists(OMEKACLI_PATH . '/.git')) {
            $c = new Client();
            $localCommitHash = rtrim(shell_exec('git -C ' . OMEKACLI_PATH . ' rev-parse master'), PHP_EOL);
            try {
                $remoteCommitHash = $c->api('repo')->commits()->all('biblibre', 'omeka-cli', array())[0]['sha'];
                echo 'omeka-cli: ';
                if ($localCommitHash == $remoteCommitHash)
                    echo 'up-to-date.' . PHP_EOL;
                else
                    echo 'new version available.' . PHP_EOL;
            } catch (\RuntimeException $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        } else {
            $remoteVersion = shell_exec('git -C ' . OMEKACLI_PATH . ' ls-remote --tags https://github.com/biblibre/omeka-cli 2>/dev/null | grep -o \'[0-9]\+\.[0-9]\+\.[0-9]\+\' | sort -rV | sed 1q');
            echo 'omeka-cli: ';
            if (OMEKACLI_VERSION == $remoteVersion)
                echo 'up-to-date.' . PHP_EOL;
            else
                echo 'new version available.' . PHP_EOL;
        }

        if ($application->isOmekaInitialized()) {
            echo 'Omeka: ';
            if (version_compare(OMEKA_VERSION, latest_omeka_version() ) >= 0)
                echo 'up-to-date.' . PHP_EOL;
            else
                echo 'new version available.' . PHP_EOL;

            echo 'Plugins:' . PHP_EOL;
            $pluginCommand = new PluginCommand();
            $pluginCommand->run(array(), array('up', '--list'), $application);
        }

        return 0;
    }
}
