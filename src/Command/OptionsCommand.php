<?php

namespace OmekaCli\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptionsCommand extends AbstractCommand
{
    public function getSynopsis($short = false)
    {
        return sprintf('%s <name> <value>', $this->getName());
    }

    protected function configure()
    {
        $this->setName('options');
        $this->setDescription('list, get and set Omeka options');

        $this->addUsage('<name>');
        $this->addUsage('');

        $this->addArgument('name', InputArgument::OPTIONAL, 'Name of option to retrieve or modify');
        $this->addArgument('value', InputArgument::OPTIONAL, 'New value of option');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $args = $input->getArguments();
        $stderr = $this->getStderr();

        $optionName = $input->getArgument('name');
        $optionValue = $input->getArgument('value');

        if (!isset($optionName)) {
            $this->showAllOptions();

            return 0;
        }

        if (!$this->isOption($optionName)) {
            $stderr->writeln('Error: Option not found');

            return 1;
        }

        $omeka = $this->getOmeka();

        $sandbox = $this->getSandbox();
        if (isset($optionValue)) {
            $omeka->set_option($optionName, $optionValue);
        } else {
            $output->writeln($omeka->get_option($optionName));
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

        $output = $this->getOutput();
        foreach ($options as $name => $value) {
            $output->writeln(sprintf('%s = %s', $name, addcslashes($value, "\r\n")));
        }
    }
}
