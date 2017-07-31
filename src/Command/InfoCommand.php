<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class InfoCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'print informations about the Omeka installation';
    }

    public function getUsage()
    {
        $usage = 'Usage:' . PHP_EOL
               . '    info' . PHP_EOL
               . PHP_EOL
               . 'Print informations about the Omeka installation.' . PHP_EOL
               . 'This command shows :'
               . '- Omeka base directory,' . PHP_EOL
               . '- version Omeka version,' . PHP_EOL
               . '- database verison,' . PHP_EOL
               . '- current admin theme,' . PHP_EOL
               . '- current public theme,' . PHP_EOL
               . '- list of active plugins,' . PHP_EOL
               . '- list of inactive plugins.' . PHP_EOL;

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        echo 'omeka-cli: ';
        if (file_exists(OMEKACLI_PATH . '/.git')) {
            $output = shell_exec('git -C ' . OMEKACLI_PATH . ' log --oneline HEAD..@{u}');
            if (empty($output))
                echo 'up-to-date' . PHP_EOL;
            else
                echo 'new version available' . PHP_EOL;
        } else {
            $remoteVersion = shell_exec('git -C ' . OMEKACLI_PATH . ' ls-remote --tags https://github.com/biblibre/omeka-cli 2>/dev/null | grep -o \'[0-9]\+\.[0-9]\+\.[0-9]\+\' | sort -rV | sed 1q');
            if (OMEKACLI_VERSION == $remoteVersion)
                echo 'up-to-date' . PHP_EOL;
            else
                echo 'new version available' . PHP_EOL;
        }

        if (!$application->isOmekaInitialized()) {
            $this->logger->error('Omeka is not initialized here.');
            return 1;
        }

        $db = get_db();
        $pluginsTable = $db->getTable('Plugin');
        $activePlugins = $pluginsTable->findBy(array('active' => 1));
        $inactivePlugins = $pluginsTable->findBy(array('active' => 0));

        echo 'Omeka base directory: ' . BASE_DIR . PHP_EOL;
        echo 'Omeka version:        ' . OMEKA_VERSION . ' - ';
        if (version_compare(OMEKA_VERSION, latest_omeka_version() ) >= 0)
            echo 'up-to-date' . PHP_EOL;
        else
            echo 'new version available' . PHP_EOL;
        echo 'Database version:     ' . get_option('omeka_version') . PHP_EOL;

        if (OMEKA_VERSION != get_option('omeka_version'))
            echo 'Warning: Omeka version and database version are not the same!' . PHP_EOL;

        echo 'Admin theme:          ' . get_option('admin_theme')  . PHP_EOL;
        echo 'Public theme:         ' . get_option('public_theme') . PHP_EOL;
        echo 'Plugins (actives):' . PHP_EOL;
        foreach ($activePlugins as $plugin)
            echo $plugin->name . ' - ' . $plugin->version . PHP_EOL;
        echo 'Plugins (inactives):' . PHP_EOL;
        foreach ($inactivePlugins as $plugin)
            echo $plugin->name . ' - ' . $plugin->version . PHP_EOL;
        echo 'Plugins to update:' . PHP_EOL;
        $pluginCommand = new PluginCommand();
        $pluginCommand->setLogger($this->logger);
        $pluginCommand->run(array(), array('up', '--list'), $application);

        return 0;
    }
}
