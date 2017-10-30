<?php

namespace OmekaCli\Command\Plugin;

use GetOptionKit\OptionCollection;
use OmekaCli\Plugin\Repository\OmekaDotOrgRepository;
use OmekaCli\Plugin\Repository\GithubRepository;

class DownloadCommand extends AbstractPluginCommand
{
    public function getDescription()
    {
        return 'download a plugin';
    }

    public function getUsage()
    {
        return "Usage:\n"
             . "\tplugin-download [<options>] <plugin-id>\n"
             . "\tpldl [<options>] <plugin-id>\n"
             . "\n"
             . "<plugin-id> is the identifier of the plugin, as returned by plugin-search\n"
             . "\n"
             . "Options:\n"
             . "\t-f, --force    Force download, even if Omeka minimum version\n"
             . "\t               requirement is not met\n";
    }

    public function getOptionsSpec()
    {
        $optionsSpec = new OptionCollection();
        $optionsSpec->add('f|force', 'Force download');

        return $optionsSpec;
    }

    public function run($options, $args)
    {
        if (count($args) != 1) {
            $this->logger->error('Bad number of arguments');
            fwrite(STDERR, $this->getUsage());

            return 1;
        }

        $repositories = array(
            new OmekaDotOrgRepository(),
            new GithubRepository(),
        );

        foreach ($repositories as $repository) {
            $repository->setLogger($this->logger);
        }

        $id = reset($args);

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
            $this->logger->error('Plugin not found');

            return 1;
        }

        $destDir = '.';

        if ($this->getContext()->getOmekaPath()) {
            $omeka = $this->getOmeka();
            $omekaMinimumVersion = $plugin['info']['omekaMinimumVersion'];
            $force = isset($options['force']) && $options['force'];
            if (version_compare($omeka->OMEKA_VERSION, $omekaMinimumVersion) < 0 && !$force) {
                $this->logger->error('The current Omeka version is too low to install this plugin. Use --force if you really want to download it.');

                return 1;
            }

            $destDir = $omeka->PLUGIN_DIR;
        }

        $this->logger->info('Downloading plugin');
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
                $this->logger->error('Destination already exists : {dest}', array('dest' => $dest));

                return 1;
            }

            if (false == rename($tempdir, $dest)) {
                $this->logger->error('Cannot move {tempdir} to {dest}', array(
                    'tempdir' => $tempdir,
                    'dest' => $dest,
                ));

                return 1;
            }

            chmod($dest, 0755);
            $this->logger->notice('Plugin downloaded into {path}', array('path' => $dest));
        } catch (\Exception $e) {
            $this->logger->error('Download failed: {message}', array('message' => $e->getMessage()));

            return 1;
        }

        return 0;
    }
}
