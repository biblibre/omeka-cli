<?php

namespace OmekaCli\Plugin\Repository;

use Github\Client as GithubClient;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\TransferException;

class GithubRepository extends AbstractRepository
{
    public function __construct()
    {
        parent::__construct();

        $this->client = new GithubClient();
        $this->client->addCache(\OmekaCli\Cache::getCachePool());
    }

    public function getDisplayName()
    {
        return 'github.com';
    }

    public function find($id)
    {
        $repository = $this->getGitRepository($id);
        if ($repository) {
            $ini = $this->getPluginIni($repository);
            if (isset($ini)) {
                return array(
                    'id' => $id,
                    'displayName' => $ini['name'],
                    'version' => $ini['version'],
                    'omekaMinimumVersion' => $ini['omeka_minimum_version'],
                );
            }
        }
    }

    public function search($query)
    {
        try {
            $repositories = $this->client->api('search')->repositories(
                $query . ' omeka in:name,description fork:true'
            )['items'];
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('Error : %s', $e->getMessage()));

            return false;
        }

        $repositories = array_filter($repositories, function ($repository) use ($query) {
            if (!preg_match("/$query/i", $repository['name'])) {
                return false;
            }

            return true;
        });

        $plugins = array();
        foreach ($repositories as $repository) {
            $id = $repository['full_name'];
            $ini = $this->getPluginIni($repository);
            if (isset($ini)) {
                $plugins[$id] = array(
                    'id' => $id,
                    'displayName' => $ini['name'],
                    'version' => $ini['version'],
                    'omekaMinimumVersion' => $ini['omeka_minimum_version'],
                );
            }
        }

        return $plugins;
    }

    public function download($id)
    {
        $exitCode = null;
        $url = 'https://github.com/' . $id . '.git';
        $tempdir = rtrim(`mktemp -d --tmpdir omeka-cli.XXXXXX`);

        system("git clone -q $url $tempdir", $exitCode);

        if ($exitCode !== 0) {
            throw new \Exception('git clone failed');
        }

        return $tempdir;
    }

    protected function getGitRepository($id)
    {
        if (false === strpos($id, '/')) {
            return null;
        }

        list($owner, $name) = explode('/', $id);

        try {
            $repository = $this->client->api('repo')->show($owner, $name);
        } catch (\Exception $e) {
            $this->output->writeln(sprintf('Error: %s', $e->getMessage()));

            return null;
        }

        return $repository;
    }

    protected function getPluginIni($repository)
    {
        $branch = $repository['default_branch'];
        $httpClient = new HttpClient();
        $repo_url = 'https://raw.githubusercontent.com/' . $repository['full_name'];
        try {
            $res = $httpClient->request('GET', $repo_url . "/$branch/plugin.ini");
        } catch (TransferException $e) {
            return null;
        }

        return parse_ini_string($res->getBody()->getContents());
    }
}
