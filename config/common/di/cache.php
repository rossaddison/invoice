<?php

declare(strict_types=1);

namespace App\Provider;

use Psr\SimpleCache\CacheInterface;
use Yiisoft\Cache\Apcu\ApcuCache;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Definitions\Reference;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;

return [
    // In-memory (APCu) instead of file I/O wherever the extension is
    // loaded — it's in this project's Alpine install list
    // (docs/PHP84_ALPINE_SETUP.md) and enabled locally on WAMP too, but
    // this app also targets docker/lamp per .env's APP_ENV switch, so it
    // isn't guaranteed everywhere; calling apcu_fetch() etc. without the
    // extension loaded is a hard fatal error, not a graceful no-op, so
    // this must be gated here rather than assumed. Backs two real
    // consumers: the FastRoute route-dispatch cache (prod-only — see
    // docs/YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md) and
    // yiisoft/rate-limiter's SimpleCacheStorage below (all environments —
    // file-based counters hit lock contention under concurrent
    // signup/login attempts; see
    // docs/RATE_LIMITER_SIGNUP_LIMITATIONS.md #3, fix option B).
    CacheInterface::class => extension_loaded('apcu') ? ApcuCache::class : FileCache::class,

    YiiCacheInterface::class => Cache::class,

    SimpleCacheStorage::class => [
        '__construct()' => [Reference::to(CacheInterface::class)],
    ],
];
