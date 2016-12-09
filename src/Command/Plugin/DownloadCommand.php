<?php

namespace OmekaCli\Command\Plugin;

use Github;
use OmekaCli\Command\AbstractCommand;
use OmekaCli\Exception\BadUsageException;

class DownloadCommand extends AbstractCommand
{
    public function getDescription()
    {
        return 'download a plugin from github';
    }

    public function getUsage()
    {
        $usage = "Usage:\n"
            . "\t plugin-download PATTERN\n";

        return $usage;
    }

    public function run($options, $args, $context)
    {
        if (empty($args)) {
            throw new BadUsageException("Missing argument");
        }

        $logger = $context->getLogger();

        $search = $args[0];
        if (false !== strpos($search, '/')) {
            list($user, $search) = explode('/', $search);
        } else {
            $user = "Omeka";
        }

        $client = new Github\Client();
        $search = "$search in:name user:$user";
        $repos = $client->api('repo')->find($search);

        if (empty($repos['repositories'])) {
            $logger->error('No repositories found');
            return 1;
        }

        if (count($repos['repositories']) > 1) {
            $logger->error('More than one repository found, please refine your search');
            return 1;
        }

        $repo = reset($repos['repositories']);

        try {
            $fileInfo = $client->api('repo')->contents()->show($repo['username'], $repo['name'], 'plugin.ini');
        } catch (\Exception $e) {
            $logger->error('Error fetching plugin.ini: {message}', array(
                'message' => $e->getMessage(),
            ));
            return 1;
        }

        $plugin = parse_ini_string(base64_decode($fileInfo['content']));
        if ($context->isOmekaInitialized() && version_compare($plugin['omeka_minimum_version'], OMEKA_VERSION) > 0) {
            $logger->warning('omeka_minimum_version = {omeka_minimum_version}, Omeka version = {omeka_version}', array(
                'omeka_minimum_version' => $plugin['omeka_minimum_version'],
                'omeka_version' => OMEKA_VERSION,
            ));
        }

        $result = $client->api('search')
            ->code("filename:Plugin.php repo:{$repo['username']}/{$repo['name']}");
        foreach ($result['items'] as $item) {
            $matches = array();
            if (preg_match('|^/([^/]+)Plugin.php|', $item['path'], $matches)) {
                $pluginName = $matches[1];
                break;
            }
        }

        if (!isset($pluginName)) {
            $logger->warning("Unable to find plugin's name");
        }

        if ($context->isOmekaInitialized()) {
            $destDir = PLUGIN_DIR . '/' . $pluginName;
        } else {
            $destDir = $pluginName;
        }

        if (file_exists($destDir)) {
            $logger->error('{destDir} already exists', array(
                'destDir' => $destDir,
            ));
            return 1;
        }

        system("git clone {$repo['url']} $destDir");
    }
}
