<?php

namespace OmekaCli\Plugin\Repository;

use Github\Client;
use OmekaCli\Plugin\Info;

abstract class GithubRepository implements RepositoryInterface
{
    abstract protected function getOwner();
    abstract protected function getPossibleRepoNames($pluginName);

    public function __construct()
    {
        $this->client = new Client;
        $this->owner = $this->getOwner();
    }

    public function getDisplayName()
    {
        return "Github ({$this->owner})";
    }

    public function find($pluginName)
    {
        $repo = $this->findRepo($pluginName);

        if ($repo) {
            $ini = $this->getPluginIni($repo['name']);

            $info = new Info;
            $info->name = $pluginName;
            $info->displayName = $ini['name'];
            $info->version = $ini['version'];
            $info->omekaMinimumVersion = $ini['omeka_minimum_version'];

            return $info;
        }
    }

    public function download($pluginName, $destDir)
    {
        $repo = $this->findRepo($pluginName);

        if (!$repo) {
            throw new \Exception("Git repository not found");
        }

        $dest = "$destDir/$pluginName";
        if (file_exists($dest)) {
            throw new \Exception("Destination $dest already exists");
        }

        $exitCode = null;
        $url = $repo['clone_url'];
        system("git clone -q $url $dest", $exitCode);

        if ($exitCode !== 0) {
            throw new \Exception("git clone failed");
        }

        return $dest;
    }

    protected function findRepo($pluginName)
    {
        foreach ($this->getPossibleRepoNames($pluginName) as $repoName) {
            try {
                $repo = $this->client->api('repo')->show($this->owner, $repoName);
            } catch (\Exception $e) {
                continue;
            }

            return $repo;
        }
    }

    protected function getPluginIni($repoName)
    {
        try {
            $fileInfo = $this->client->api('repo')->contents()->show($this->owner, $repoName, 'plugin.ini');
        } catch (\Exception $e) {
            throw new \Exception('Error fetching plugin.ini', 0, $e);
        }

        return parse_ini_string(base64_decode($fileInfo['content']));
    }
}
