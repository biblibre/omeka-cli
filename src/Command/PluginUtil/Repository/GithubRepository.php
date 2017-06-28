<?php

namespace OmekaCli\Command\PluginUtil\Repository;

use Github\Client;
use Github\Exception\RuntimeException;

class GithubRepository implements RepositoryInterface
{
    protected $selectedRepo;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getDisplayName()
    {
        return "github.com";
    }

    public function find($pluginName)
    {
        $this->selectedRepo = $this->findRepo($pluginName);

        if ($this->selectedRepo) {
            $ini = $this->getPluginIni(
                $this->selectedRepo['owner']['login'],
                $this->selectedRepo['name']
            );
            if (!empty($ini)) {
                $info = array(
                    'name'                => $pluginName,
                    'displayName'         => $ini['name'],
                    'version'             => $ini['version'],
                    'omekaMinimumVersion' => $ini['omeka_minimum_version'],
                );
            }
        }

        return (isset($info)) ? $info : null;
    }

    public function download($pluginName, $destDir)
    {
        $dest = $destDir . '/' . $this->selectedRepo['name'];
        if (file_exists($dest))
            throw new \Exception("destination $dest already exists");

        $exitCode = null;
        $url = $this->selectedRepo['clone_url'];
        system("git clone -q $url $dest", $exitCode);

        if ($exitCode !== 0)
            throw new \Exception('git clone failed');

        return $dest;
    }

    protected function findRepo($pluginName)
    {
        try {
            $repos = $this->client->api('search')->repositories(
                $pluginName . ' language:php language:html'
            );
        } catch (\Exception $e) {
            echo "Warning: something bad occured during GitHub searching.\n";
            return;
        }

        $chosen = 0;
        if ($repos['total_count'] == 0) {
            return;
        } else if ($repos['total_count'] > 1) {
            do {
                echo "Many repositories found, choose one.\n";
                $i = 0;
                foreach ($repos['items'] as $repo)
                    printf("%d) %s/%s\n",
                           $i++,
                           $repo['owner']['login'],
                           $repo['name']);
                $chosen = trim(fgets(STDIN));
            } while (!is_numeric($chosen) || $chosen < 0 || $chosen > $i - 1);
        }

        return $repos['items'][$chosen];
    }

    protected function getPluginIni($repoOwner, $repoName)
    {
        try {
            $fileInfo = $this->client->api('repo')->contents()->show(
                "$repoOwner",
                "$repoName",
                'plugin.ini'
            );
        } catch (\RuntimeException $e) {
            echo 'Error: this is probably not an Omeka plugin. Continuing.'
               . "\n";
        }

        return (isset($fileInfo))
             ? parse_ini_string(base64_decode($fileInfo['content']))
             : null;
    }
}
