<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

use Github\Client;
use Github\Exception\RuntimeException;

class UpgradeCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'upgrade omeka-cli or Omeka';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    upgrade' . PHP_EOL
               . PHP_EOL
               . 'Upgrade omeka-cli or Omeka.' . PHP_EOL;

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
            $localCommitHash = rtrim(shell_exec('git -C ' . OMEKACLI_PATH . ' rev-parse master'), PHP_EOL);
            try {
                $c = new Client();
                $remoteCommitHash = $c->api('repo')->commits()->all('biblibre', 'omeka-cli', array())[0]['sha'];
                if ($localCommitHash == $remoteCommitHash)
                    echo 'omeka-cli is up-to-date.' . PHP_EOL;
                else
                    echo 'New version of omeka-cli available.' . PHP_EOL;
//                    shell_exec('git -C ' . OMEKACLI_PATH . '/' . $plugin->name . ' pull');
            } catch (\RuntimeException $e) {
                echo $e->getMessage() . PHP_EOL;
            }
        } else {
            shell_exec('git -C ' . OMEKACLI_PATH . ' ls-remote --tags 2>/dev/null | grep -o \'[0-9]\+\.[0-9]\+\.[0-9]\+\' | sort -rV | sed 1q');
        }

        return 0;
    }
}
