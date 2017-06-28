<?php

namespace OmekaCli\Command\PluginUtil\Repository;

use Goutte\Client;
use ZipArchive;
use phpFastCache\CacheManager;
use DateInterval;

class OmekaDotOrgRepository implements RepositoryInterface
{
    const PLUGINS_URL = 'http://omeka.org/add-ons/plugins/';

    protected $client;

    public function __construct()
    {
        $this->client = new Client();
    }

    public function getDisplayName()
    {
        return 'Omeka.org';
    }

    public function find($pluginName)
    {
        $plugin = $this->findPlugin($pluginName);

        if ($plugin) {
            $info = array(
                'name'                => $pluginName,
                'displayName'         => $plugin['display_name'],
                'version'             => $plugin['version'],
                'omekaMinimumVersion' => $plugin['omeka_minimum_version'],
            );

            return $info;
        }
    }

    public function download($pluginName, $destDir)
    {
        $plugin = $this->findPlugin($pluginName);

        if (!$plugin) {
            throw new \Exception('Plugin not found');
        }

        $url = $plugin['download_url'];
        $tmpDir = $this->tempDir();
        $tmpZip = "$tmpDir/$pluginName.zip";
        file_put_contents($tmpZip, fopen($url, 'r'));

        $handle = zip_open($tmpZip);
        $dirhandle = zip_read($handle);
        $realPluginName = rtrim(zip_entry_name($dirhandle), '/');
        zip_entry_close($dirhandle);
        zip_close($handle);

        $dest = "$destDir/$realPluginName";
        if (file_exists($dest)) {
            throw new \Exception("$dest already exists");
        }

        $zip = new ZipArchive();
        if (true === $zip->open($tmpZip)) {
            $zip->extractTo($destDir);
            $zip->close();
        }

        return $dest;
    }

    protected function findPlugin($pluginName)
    {
        $plugins = $this->getPlugins();

        $camelCaseSplitRegex = '/(?<!^)(?=[A-Z][a-z])|(?<=[a-z])(?=[A-Z])/';
        $words = preg_split($camelCaseSplitRegex, $pluginName);
        $wordsRegex = implode('.*', $words);

        $plugins = array_filter($plugins, function ($plugin) use ($wordsRegex) {
            return 1 == preg_match("/$wordsRegex/i", $plugin['download_url']);
        });

        $plugin = null;
        if (!empty($plugins)) {
            $plugin = reset($plugins);
            $root = $this->client->request('GET', $plugin['url']);
            $tr = $root->filter('table.omeka-addons-versions tr')->eq(1);
            $plugin['version'] = $tr->filter('a')->text();
            $plugin['url'] = $tr->filter('a')->link()->getUri();
            $plugin['omeka_minimum_version'] = $tr->filter('td')->eq(1)->text();
        }

        return $plugin;
    }

    protected function getPlugins()
    {
        $cache = CacheManager::getInstance('Files');
        $cacheKey = __METHOD__;
        $cacheItem = $cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $root = $this->client->request('GET', self::PLUGINS_URL);
            $downloadLinks = $root
                ->filter('a.omeka-addons-button');

            $plugins = array();
            for ($i = 0; $i < count($downloadLinks); ++$i) {
                $downloadLink = $downloadLinks->eq($i);
                $pluginLink = $downloadLink->parents()->parents()->parents()->filter('h2 > a');
                $plugins[] = array(
                    'display_name' => $pluginLink->text(),
                    'url' => $pluginLink->link()->getUri(),
                    'download_url' => $downloadLink->link()->getUri(),
                );
            }

            $ttl = DateInterval::createFromDateString('1 day');
            $cacheItem->set($plugins)->expiresAfter($ttl);
            $cache->save($cacheItem);
        }

        return $cacheItem->get();
    }

    protected function tempDir()
    {
        $tempDir = null;
        $sysTmpDir = sys_get_temp_dir();
        $tries = 0;
        do {
            $tempDir = "$sysTmpDir/omeka-cli." . dechex(rand());
            ++$tries;
        } while (true !== @mkdir($tempDir) && $tries < 5);

        return $tempDir;
    }
}
