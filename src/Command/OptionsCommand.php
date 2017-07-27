<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class OptionsCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'edit and see the "omeka_options" table';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
               . "\toptions OPTION [VALUE]\n"
               . "\n"
               . "Edit and retrieve elements from the \"omeka_options\" table.\n"
               . "\n"
               . "OPTION\n"
               . "\tthe name of the option to retrieve.\n"
               . "VALUE\n"
               . "\tif set, the new value of the option.\n"
               . "NB\n"
               . "\tThis command return an empty line in those cases:\n"
               . "\t- the option does not exists ;\n"
               . "\t- the option actually has no value.\n"
               . "\tWith -g and -s options, the command always show the new or current value of the option.\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (count($args) == 0 || count($args) > 2) {
            echo $this->getUsage();
            return 1;
        }

        $db = get_db();
        $optionsTable = $db->getTable('Option');
        $query = $optionsTable->findBy(array('name' => $args[0]));
        if (!$query) {
            $this->logger->error('option ' . $args[0] . ' not found.');
            return 1;
        }

        if (count($args) == 2)
            set_option($args[0], $args[1]);
        echo get_option($args[0]) . PHP_EOL;

        return 0;
    }
}
