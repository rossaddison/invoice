<?php

declare(strict_types=1);

namespace App\Provider;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;

return [
    CacheInterface::class => FileCache::class,

    YiiCacheInterface::class => Cache::class,
    
    SimpleCacheStorage::class => [
        '__construct()' => [Reference::to(CacheInterface::class)],
    ],
];