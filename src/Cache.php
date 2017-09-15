<?php

namespace OmekaCli;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Cache\Adapter\Filesystem\FilesystemCachePool;

class Cache
{
    protected static $cachePool;

    public static function getCachePool()
    {
        if (!isset(self::$cachePool)) {
            $cacheHome = getenv('XDG_CACHE_HOME');
            if (empty($cacheHome)) {
                $cacheHome = getenv('HOME') . '/.cache';
            }
            $cacheDir = "$cacheHome/omeka-cli";

            $filesystem = new Filesystem(new Local($cacheDir));
            self::$cachePool = new FilesystemCachePool($filesystem);
        }

        return self::$cachePool;
    }
}
