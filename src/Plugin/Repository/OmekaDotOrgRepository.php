<?php

namespace OmekaCli\Plugin\Repository;

use Goutte\Client;
use ZipArchive;
use DateInterval;
use OmekaCli\Cache;

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
                'name' => $pluginName,
                'displayName' => $plugin['display_name'],
                'version' => $plugin['version'],
                'omekaMinimumVersion' => $plugin['omeka_minimum_version'],
            );

            return array($info);
        }
    }

    public function download($plugin, $destDir)
    {
        $pluginName = $plugin['name'];
        $plugin = $this->findPlugin($pluginName);

        if (!$plugin) {
            throw new \Exception('Plugin not found');
        }

        $url = $plugin['download_url'];
        $tmpDir = $this->tempDir();
        if (!isset($tmpDir)) {
            throw new \Exception('Failed to create temporary directory');
        }

        $tmpZip = "$tmpDir/$pluginName.zip";
        file_put_contents($tmpZip, fopen($url, 'r'));

        $zip = new ZipArchive();
        if (true !== $zip->open($tmpZip)) {
            throw new \Exception("Failed to open ZIP file '$tmpZip'");
        }

        $resultDir = "$tmpDir/result";
        mkdir($resultDir, 0700);
        $zip->extractTo($resultDir);
        $zip->close();

        $dh = opendir($resultDir);
        while (false !== ($entry = readdir($dh))) {
            if ($entry != '.' && $entry != '..') {
                $realPluginName = $entry;
                break;
            }
        }
        closedir($dh);

        $tmpPluginDir = "$resultDir/$realPluginName";

        if (isset($destDir)) {
            $pluginDir = "$destDir/$realPluginName";
            if (file_exists($pluginDir)) {
                throw new \Exception("$pluginDir already exists");
            }

            rename($tmpPluginDir, $pluginDir);

            return $pluginDir;
        }

        return $tmpPluginDir;
    }

    public function findPlugin($pluginName)
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
        $cache = Cache::getCachePool();
        $cacheKey = 'OmekaDotOrgRepository.plugins';
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
        $tempFile = tempnam(sys_get_temp_dir(), 'omeka-cli.');
        if (false !== $tempFile) {
            unlink($tempFile);
            if (false !== @mkdir($tempFile, 0700)) {
                return $tempFile;
            }
        }
    }
}
