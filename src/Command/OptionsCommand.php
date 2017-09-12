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
        return 'Usage:' . PHP_EOL
             . '    options [OPTION_NAME [VALUE]]' . PHP_EOL
             . PHP_EOL
             . 'Edit and see the "omeka_options" table.' . PHP_EOL
             . PHP_EOL
             . 'OPTION_NAME' . PHP_EOL
             . '    the name of the option to retrieve.' . PHP_EOL
             . 'VALUE' . PHP_EOL
             . '    if set, the new value of the option.' . PHP_EOL
             . PHP_EOL
             . 'This command return an empty line in those cases:' . PHP_EOL
             . '- the option does not exists ;' . PHP_EOL
             . '- the option has no value.' . PHP_EOL;
    }

    public function run($options, $args, Application $application)
    {
        switch (count($args)) {
        case '0':
            $this->showAllOptions();
            break;
        case '1':
            if (!$this->isOption($args[0])) {
                $this->logger->error('option not found');

                return 1;
            }
            echo get_option($args[0]) . PHP_EOL;
            break;
        case '2':
            if (!$this->isOption($args[0])) {
                $this->logger->error('this option does not exists');

                return 1;
            }
            set_option($args[0], $args[1]);
            echo get_option($args[0]) . PHP_EOL;
            break;
        default:
            $this->logger->error($this->getUsage());

            return 1;
        }

        return 0;
    }

    protected function isOption($optionName)
    {
        $db = get_db();
        $optionsTable = $db->getTable('Option');
        $query = $optionsTable->findBy(array('name' => $optionName));

        return !empty($query);
    }

    protected function showAllOptions()
    {
        $db = get_db();
        $optionsTable = $db->getTable('Option');
        $options = $optionsTable->findAll();
        foreach ($options as $option) {
            echo $option['name'] . '=' . $option['value'] . PHP_EOL;
        }
    }
}
