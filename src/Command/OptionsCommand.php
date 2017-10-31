<?php

namespace OmekaCli\Command;

class OptionsCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'list, get and set Omeka options';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "    options\n"
             . "    options <name>\n"
             . "    options <name> <value>\n"
             . "\n"
             . "The first form lists all options and their value.\n"
             . "The second form prints the value of option <name>.\n"
             . "The third form sets the value of option <name> to <value>.\n";
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

        $omeka = $this->getOmeka();

        $sandbox = $this->getSandbox();
        if (isset($optionValue)) {
            $omeka->set_option($optionName, $optionValue);
        } else {
            echo $omeka->get_option($optionName) . "\n";
        }

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
