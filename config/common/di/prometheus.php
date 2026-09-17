<?php

declare(strict_types=1);

use App\Invoice\Prometheus\PrometheusController;
use App\Invoice\Prometheus\PrometheusMiddleware;
use App\Invoice\Prometheus\PrometheusService;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\APC;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Yiisoft\Definitions\Reference;

return [
    PrometheusService::class => [
        'class' => PrometheusService::class,
        '__construct()' => [
            'config' => [
                'storage' => [
                    // 'memory' only lives inside a single PHP process -- on
                    // PHP-FPM/Apache every request is a fresh process with
                    // nothing shared, so metrics never survive to the next
                    // scrape. 'apcu' is the default since this app already
                    // requires ext-apcu, giving real cross-request
                    // persistence with zero extra infrastructure.
                    'type' => $_ENV['PROMETHEUS_STORAGE_TYPE'] ?? 'apcu',
                    'redis' => [
                        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                        'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 0.1),
                        'read_timeout' => (int) ($_ENV['REDIS_READ_TIMEOUT'] ?? 10),
                        'persistent_connections' => (bool) ($_ENV['REDIS_PERSISTENT'] ?? false),
                    ],
                ],
                'namespace_prefix' => 'yii3_invoice',
                'enabled' => (bool) ($_ENV['PROMETHEUS_ENABLED'] ?? true),
                'collect_system_metrics' => true,
                'collect_business_metrics' => true,
                'collect_http_metrics' => true,
                'grafana_compatible' => true,
                'node_exporter_compatible' => true,
                'windows_exporter_compatible' => PHP_OS_FAMILY === 'Windows',
            ],
        ],
    ],

    CollectorRegistry::class => static function (): CollectorRegistry {
        // Same default/rationale as PrometheusService::class's config above --
        // keep both in sync, since PrometheusMiddleware writes to this
        // registry while PrometheusService/PrometheusController reads from
        // its own separately-constructed one; they only end up seeing the
        // same data when both resolve to the same real (apcu/redis) backend.
        $storageType = $_ENV['PROMETHEUS_STORAGE_TYPE'] ?? 'apcu';

        return match ($storageType) {
            'redis' => new CollectorRegistry(
                new Redis([
                    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                    'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 0.1),
                    'read_timeout' => (int) ($_ENV['REDIS_READ_TIMEOUT'] ?? 10),
                    'persistent_connections' => (bool) ($_ENV['REDIS_PERSISTENT'] ?? false),
                ])
            ),
            'apcu' => extension_loaded('apcu') && class_exists(APC::class) && apcu_enabled()
                ? new CollectorRegistry(new APC())
                : new CollectorRegistry(new InMemory()),
            default => new CollectorRegistry(new InMemory()),
        };
    },

    PrometheusMiddleware::class => [
        'class' => PrometheusMiddleware::class,
        '__construct()' => [
            Reference::to(CollectorRegistry::class),
        ],
    ],

    PrometheusController::class => [
        'class' => PrometheusController::class,
        '__construct()' => [
            'prometheusService' => Reference::to(PrometheusService::class),
        ],
    ],
];
