<?php

namespace OmekaCli\Command;

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

    public function run($options, $args)
    {
        if (count($args) > 2) {
            $this->logger->error($this->getUsage());

            return 1;
        }

        list($optionName, $optionValue) = array_pad($args, 2, null);

        if (!isset($optionName)) {
            $this->showAllOptions();

            return 0;
        }

        if (!$this->isOption($optionName)) {
            $this->logger->error('option not found');

            return 1;
        }

        $sandbox = $this->getSandbox();
        if (isset($optionValue)) {
            $sandbox->execute(function () use ($optionName, $optionValue) {
                set_option($optionName, $optionValue);
            });
        }

        $optionValue = $sandbox->execute(function () use ($optionName) {
            return get_option($optionName);
        });

        echo "$optionValue\n";

        return 0;
    }

    protected function isOption($optionName)
    {
        $sandbox = $this->getSandbox();
        $isOption = $sandbox->execute(function () use ($optionName) {
            $db = get_db();
            $optionsTable = $db->getTable('Option');
            $options = $optionsTable->findBy(array('name' => $optionName));

            return !empty($options);
        });

        return $isOption;
    }

    protected function showAllOptions()
    {
        $sandbox = $this->getSandbox();
        $options = $sandbox->execute(function () {
            $db = get_db();
            $optionsTable = $db->getTable('Option');
            $options = array();
            foreach ($optionsTable->findAll() as $option) {
                $options[$option->name] = $option->value;
            }

            return $options;
        });

        foreach ($options as $name => $value) {
            echo sprintf('%s = %s', $name, $value) . "\n";
        }
    }
}
