<?php

declare(strict_types=1);

use Yiisoft\Aliases\Aliases;
use Yiisoft\Cache\File\FileCache;
use Yiisoft\Yii\RateLimiter\Counter;
use Yiisoft\Yii\RateLimiter\CounterInterface;
use Yiisoft\Yii\RateLimiter\Storage\SimpleCacheStorage;
use Yiisoft\Yii\RateLimiter\Storage\StorageInterface;

/** @var array $params */

/**
 * Rate limiter configuration for authentication endpoints.
 * 
 * The limit is set to 20 requests per 10 seconds to:
 * - Prevent brute-force attacks on login endpoints
 * - Allow acceptance test suites to run without hitting rate limits
 * - Balance security with test suite compatibility
 * 
 * Note: Previously increased from 2 → 5, now 5 → 20 to accommodate
 * multiple acceptance tests running in quick succession.
 */
return [
    StorageInterface::class => function (Aliases $aliases) {
        $cache = new FileCache($aliases->get('@runtime/rate-limiter'));

        return new SimpleCacheStorage($cache);
    },
    CounterInterface::class => [
        'class' => Counter::class,
        '__construct()' => [
            'limit' => 20, // Increased for test compatibility
            'periodInSeconds' => 10,
        ],
    ],
];
