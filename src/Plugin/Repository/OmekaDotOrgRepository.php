<?php

namespace OmekaCli\Plugin\Repository;

use ZipArchive;
use DateInterval;
use OmekaCli\Cache;

class OmekaDotOrgRepository extends AbstractRepository
{
    const PLUGINS_URL = 'https://raw.githubusercontent.com/biblibre/omeka-addons-index/master/data/plugins.json';

    public function getDisplayName()
    {
        return 'Omeka.org';
    }

    public function find($id)
    {
        $plugins = $this->getPlugins();

        if (!array_key_exists($id, $plugins)) {
            return null;
        }

        $plugin = array(
            'id' => $id,
            'displayName' => $plugins[$id]['versions'][0]['info']['name'],
            'version' => $plugins[$id]['versions'][0]['info']['version'],
            'omekaMinimumVersion' => $plugins[$id]['versions'][0]['info']['omeka_minimum_version'],
        );

        return $plugin;
    }

    public function search($query)
    {
        $plugins = $this->getPlugins();

        $ids = array();
        foreach ($plugins as $id => $plugin) {
            $info = $plugin['versions'][0]['info'];
            if (
               preg_match("/$query/i", $id)
            || preg_match("/$query/i", $info['name'])
            || isset($info['description']) && preg_match("/$query/i", $info['description'])
            || isset($info['tags']) && preg_match("/$query/i", $info['tags'])) {
                $ids[] = $id;
            }
        }

        $results = array();
        foreach ($ids as $id) {
            $plugin = $plugins[$id]['versions'][0];
            $results[$id] = array(
                'id' => $id,
                'displayName' => $plugin['info']['name'],
                'version' => $plugin['info']['version'],
                'omekaMinimumVersion' => $plugin['info']['omeka_minimum_version'],
            );
        }

        return $results;
    }

    public function download($id)
    {
        $plugin = $this->getPlugin($id);

        if (!$plugin) {
            throw new \Exception('Plugin not found');
        }

        $url = $plugin['versions'][0]['url'];
        $tmpDir = rtrim(`mktemp -d --tmpdir omeka-cli.XXXXXX`);
        if (!isset($tmpDir)) {
            throw new \Exception('Failed to create temporary directory');
        }

        $tmpZip = "$tmpDir/$id.zip";
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

        return $tmpPluginDir;
    }

    protected function getPlugin($id)
    {
        $plugins = $this->getPlugins();

        return isset($plugins[$id]) ? $plugins[$id] : null;
    }

    protected function getPlugins()
    {
        $cache = Cache::getCachePool();
        $cacheKey = 'OmekaDotOrgRepository.plugins';
        $cacheItem = $cache->getItem($cacheKey);

        if (!$cacheItem->isHit()) {
            $plugins = json_decode(file_get_contents(self::PLUGINS_URL), true);

            $ttl = DateInterval::createFromDateString('1 day');
            $cacheItem->set($plugins)->expiresAfter($ttl);
            $cache->save($cacheItem);
        }

        return $cacheItem->get();
    }
}
