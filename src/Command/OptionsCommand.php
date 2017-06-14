<?php

namespace OmekaCli\Command;

use OmekaCli\Application;

class OptionsCommand extends AbstractCommand
{
    public function getOptionsSpec()
    {
        return array(
            'get' => array(
                'short' => 'g',
            ),
            'set' => array(
                'short' => 's',
            ),
        );
    }

    public function getDescription()
    {
        return 'edit and see the "omeka_options" table';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
               . "\toptions [SUBCOMMAND OPTION [VALUE]]\n"
               . "\n"
               . "Edit and see the \"omeka_options\" table.\n"
               . "\n"
               . "SUBCOMMAND\n"
               . "\t-g  get the OPTION,\n"
               . "\t-s  set the OPTION.\n"
               . "OPTION\n"
               . "\tthe name of the option to act on.\n"
               . "VALUE\n"
               . "\tif -g is set, the new value of the option.\n"
               . "NB\n"
               . "\tThis command return an empty line in those cases:\n"
               . "\t- the option does not exists ;\n"
               . "\t- the option actually has no value.\n"
               . "\tWith -g and -s options, the command always show the new or current value of the option.\n";

        return $usage;
    }

    public function run($options, $args, Application $application)
    {
        if (empty($options) || empty($args)) {
            echo $this->getUsage();
            return 1;
        }

        switch (array_search(1, $options)) { // TODO handle serialized data
            case "set":
                if (!empty(get_option($args[0])))
                    set_option($args[0], $args[1]);
                // FALLTHROUGH
            case "get":
                echo get_option($args[0]) . "\n";
                break;
            default:
                echo $this->getUsage();
                return 1;
        }

        return 0;
    }
}
