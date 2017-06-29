<?php

namespace OmekaCli\Command\PluginUtil\Repository;

use Github\Client;
use Github\Exception\RuntimeException;

class GithubRepository implements RepositoryInterface
{
    protected static $url; // TODO change it, this is madness.

    public function __construct()
    {
        $this->client = new Client();
    }

    public static function setUrl($newurl)
    {
        self::$url = $newurl;
    }

    public function getDisplayName()
    {
        return 'github.com';
    }

    public function find($pluginName)
    {
        $possibleRepos = $this->findRepo($pluginName);
        $infos = array();

        foreach ($possibleRepos as $repo) {
            $ini = $this->getPluginIni(
                $repo['owner']['login'],
                $repo['name']
            );
            if (isset($ini)) {
                $infos[] = array(
                    'name'                => $pluginName,
                    'displayName'         => $ini['name'],
                    'version'             => $ini['version'],
                    'omekaMinimumVersion' => $ini['omeka_minimum_version'],
                    'url'                 => $repo['clone_url'],
                );
            }
        }

        return (!empty($infos)) ? $infos : null;
    }

    public function download($pluginName, $destDir)
    {
        $dest = $destDir . '/' . $pluginName;
        if (file_exists($dest))
            throw new \Exception("destination $dest already exists");

        $exitCode = null;
        $url = self::$url;
        system("git clone -q $url $dest", $exitCode);

        if ($exitCode !== 0)
            throw new \Exception('git clone failed');

        return $dest;
    }

    protected function findRepo($pluginName)
    {
        try {
            $repos = $this->client->api('search')->repositories(
                $pluginName . ' omeka in:name,description,readme'
            );
        } catch (\Exception $e) {
            echo "Warning: something bad occured during GitHub searching.\n";
            return;
        }

        return $repos['items'];
    }

    protected function getPluginIni($repoOwner, $repoName)
    {
        try {
            $fileInfo = $this->client->api('repo')->contents()->show(
                "$repoOwner",
                "$repoName",
                'plugin.ini'
            );
        } catch (\RuntimeException $e) { }

        return (isset($fileInfo))
             ? parse_ini_string(base64_decode($fileInfo['content']))
             : null;
    }
}
