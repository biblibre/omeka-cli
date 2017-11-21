<?php

namespace OmekaCli\Command;

use OmekaCli\Omeka\PluginInstaller;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PluginDisableCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('plugin-disable');
        $this->setDescription('disable a plugin');
        $this->setAliases(array('dis'));
        $this->addArgument('name', InputArgument::REQUIRED, 'the name of plugin to disable');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $omekaPath = $this->getContext()->getOmekaPath();
        if (!$omekaPath) {
            $stderr->writeln('Error: Not in an Omeka directory');

            return 1;
        }

        $pluginName = $input->getArgument('name');

        $pluginInstaller = new PluginInstaller();
        $pluginInstaller->setContext($this->getContext());

        try {
            $pluginInstaller->disable($pluginName);
        } catch (\Exception $e) {
            $stderr->writeln('Error: ' . $e->getMessage());

            return 1;
        }

        $stderr->writeln(sprintf('%s disabled', $pluginName));

        return 0;
    }
}
