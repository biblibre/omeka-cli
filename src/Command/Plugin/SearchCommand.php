<?php

namespace OmekaCli\Command\Plugin;

use OmekaCli\Command\AbstractCommand;
use OmekaCli\Plugin\Repository\GithubRepository;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SearchCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('plugin-search');
        $this->setDescription('search plugins');
        $this->setAliases(array('search'));
        $this->addArgument('query', InputArgument::REQUIRED);
        $this->addOption('exclude-github', 'G', InputOption::VALUE_NONE, 'do not search plugins on Github');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $repositories = array();
        $repositories[] = new OmekaDotOrgRepository();
        if (!isset($options['exclude-github']) || !$options['exclude-github']) {
            $repositories[] = new GithubRepository();
        }

        $query = $input->getArgument('query');

        $plugins = array();
        foreach ($repositories as $repository) {
            $pluginsInfo = $repository->search($query);
            foreach ($pluginsInfo as $pluginInfo) {
                $plugins[] = array(
                    'info' => $pluginInfo,
                    'repository' => $repository,
                );
            }
        }

        foreach ($plugins as $plugin) {
            $output->writeln(sprintf('%1$s (%2$s) - %3$s',
                $plugin['info']['id'],
                $plugin['info']['version'],
                $plugin['repository']->getDisplayName()
            ));
        }

        return 0;
    }
}
