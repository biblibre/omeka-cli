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
        $usage = "Usage:\n"
               . "\tinfo\n"
               . "\n"
               . "Print informations about the Omeka installation.\n"
               . "This command shows :"
               . " * the Omeka base directory,\n"
               . " * the version Omeka version,\n"
               . " * the database verison,\n"
               . " * the current admin theme,\n"
               . " * the current public theme,\n"
               . " * the list of active plugins,\n"
               . " * the list of inactive plugins.\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (!$application->isOmekaInitialized()) {
            echo "Error: Omeka is not initialized here.\n";
            return 1;
        }

        $db = get_db();
        $pluginsTable = $db->getTable('Plugin');
        $activePlugins = $pluginsTable->findBy(array('active' => 1));
        $inactivePlugins = $pluginsTable->findBy(array('active' => 0));

        echo "Omeka base directory: " . BASE_DIR                    . "\n";
        echo "Omeka version         " . OMEKA_VERSION               . "\n";
        echo "Database version:     " . get_option('omeka_version') . "\n";

        if (OMEKA_VERSION == get_option('omeka_version'))
            echo "Warning: Omeka version and database version are not the same!\n";

        echo "Admin theme:          " . get_option('admin_theme')   . "\n";
        echo "Public theme:         " . get_option('public_theme')  . "\n";
        echo "Plugins (actives):\n";
        foreach ($activePlugins as $plugin)
            echo $plugin->name . " - " . $plugin->version . "\n";
        echo "Plugins (inactives):\n";
        foreach ($inactivePlugins as $plugin)
            echo $plugin->name . " - " . $plugin->version . "\n";
    }
}

?>
