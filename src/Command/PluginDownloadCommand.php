<?php

namespace OmekaCli\Command;

use OmekaCli\Plugin\Repository\GithubRepository;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PluginDownloadCommand extends AbstractCommand
{
    protected function configure()
    {
        $this->setName('plugin-download');
        $this->setDescription('download a plugin');
        $this->setAliases(array('dl'));
        $this->addArgument('plugin-id', InputArgument::REQUIRED, 'the identifier of the plugin, as returned by plugin-search');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force download, even if Omeka minimum version requirement is not met');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stderr = $this->getStderr();

        $repositories = array(
            new OmekaDotOrgRepository(),
            new GithubRepository(),
        );

        foreach ($repositories as $repository) {
            $repository->setOutput($stderr);
        }

        $id = $input->getArgument('plugin-id');

        $plugin = null;
        foreach ($repositories as $repository) {
            $pluginInfo = $repository->find($id);
            if ($pluginInfo) {
                $plugin = array(
                    'info' => $pluginInfo,
                    'repository' => $repository,
                );

                break;
            }
        }

        if (!isset($plugin)) {
            $stderr->writeln('Error: Plugin not found');

            return 1;
        }

        $destDir = '.';

        if ($this->getContext()->getOmekaPath()) {
            $omeka = $this->getOmeka();
            $omekaMinimumVersion = $plugin['info']['omekaMinimumVersion'];
            $force = isset($options['force']) && $options['force'];
            if (version_compare($omeka->OMEKA_VERSION, $omekaMinimumVersion) < 0 && !$force) {
                $stderr->writeln('The current Omeka version is too low to install this plugin. Use --force if you really want to download it.');

                return 1;
            }

            $destDir = $omeka->PLUGIN_DIR;
        }

        if ($stderr->isVerbose()) {
            $stderr->writeln('Downloading plugin');
        }

        try {
            $repository = $plugin['repository'];
            $tempdir = $repository->download($id);

            foreach (scandir($tempdir) as $file) {
                $matches = array();
                if (preg_match('/(.*)Plugin.php$/', $file, $matches)) {
                    $pluginName = $matches[1];
                    break;
                }
            }

            if (!isset($pluginName)) {
                // Try to guess plugin name from id
                $parts = preg_split('/[\/-]/', $id);
                $pluginName = end($parts);
            }

            $dest = $destDir . '/' . $pluginName;
            if (file_exists($dest)) {
                $stderr->writeln(sprintf('Destination already exists : %s', $dest));

                return 1;
            }

            if (false == rename($tempdir, $dest)) {
                $stderr->writeln(sprintf('Cannot move %1$s to %2$s', $tempdir, $dest));

                return 1;
            }

            chmod($dest, 0755);
            $stderr->writeln(sprintf('Plugin downloaded into %s', $dest));
        } catch (\Exception $e) {
            $stderr->writeln(sprintf('Download failed: %s', $e->getMessage()));

            return 1;
        }

        return 0;
    }
}
