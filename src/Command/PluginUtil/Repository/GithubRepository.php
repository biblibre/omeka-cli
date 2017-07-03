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
            $reposToFilter = $this->client->api('search')->repositories(
                $pluginName . ' omeka in:name,description fork:true'
            )['items'];
        } catch (\Exception $e) {
            echo "Warning: something bad occured during GitHub searching.\n";
            return;
        }

        $repos = array();
        for ($i = 0; $i < count($reposToFilter); $i++)
            if (preg_match("/$pluginName/i", $reposToFilter[$i]['name']))
                $repos[] = $reposToFilter[$i];

        return $this->filterPlugins($repos);
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

    protected function filterPlugins($repos)
    {
        $ans = array();

        foreach ($repos as $repo) {
            $files = $this->client->api('repo')->contents()->show(
                $repo['owner']['login'],
                $repo['name']
            );

            // Search <name>Plugin.php file.
            $phpFileFound = false;
            foreach ($files as $file)
                if (preg_match('/.+Plugin\.php/i', $file['name']))
                    $phpFileFound = true;

            // Search plugin.ini file.
            $iniFileFound = false;
            foreach ($files as $file)
                if (preg_match('/plugin\.ini/i', $file['name']))
                    $iniFileFound = true;

            // Check if both have been found.
            if ($iniFileFound && $phpFileFound)
                $ans[] = $repo;
        }

        return $ans;
    }
}
