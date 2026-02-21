<?php

declare(strict_types=1);

/**
 * Prometheus Monitoring Configuration for Yii3 Invoice Application
 *
 * This configuration sets up Prometheus metrics collection with support for:
 * - HTTP request metrics (compatible with Grafana dashboards)
 * - Business metrics specific to the invoice application
 * - System metrics that complement node_exporter and windows_exporter
 * - Integration with rfmoz/grafana-dashboards
 */

use App\Invoice\Prometheus\PrometheusService;
use App\Invoice\Prometheus\PrometheusMiddleware;
use App\Invoice\Prometheus\PrometheusController;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Yiisoft\Definitions\Reference;

return [
    // Prometheus Service Configuration
    PrometheusService::class => [
        'class' => PrometheusService::class,
        '__construct()' => [
            'config' => [
                'storage' => [
                    // Storage type: 'memory', 'redis', or 'apcu'
                    // For production, use 'redis' or 'apcu' for persistence
                    // across requests
                    'type' => $_ENV['PROMETHEUS_STORAGE_TYPE'] ?? 'memory',
                    'redis' => [
                        'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                        'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                        'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 0.1),
                        'read_timeout' => (int) ($_ENV['REDIS_READ_TIMEOUT'] ?? 10),
                        'persistent_connections' => (bool)
                            ($_ENV['REDIS_PERSISTENT'] ?? false),
                    ]
                ],
                'namespace_prefix' => 'yii3_invoice',
                'enabled' => (bool) ($_ENV['PROMETHEUS_ENABLED'] ?? true),
                
                // Metric collection configuration
                'collect_system_metrics' => true,
                'collect_business_metrics' => true,
                'collect_http_metrics' => true,
                
                // Integration settings
                'grafana_compatible' => true,
                'node_exporter_compatible' => true,
                'windows_exporter_compatible' => PHP_OS_FAMILY === 'Windows',
            ]
        ]
    ],

    // Prometheus Middleware Configuration
    PrometheusMiddleware::class => [
        'class' => PrometheusMiddleware::class,
        '__construct()' => [
            Reference::to(CollectorRegistry::class)
        ]
    ],

    // Prometheus Controller Configuration
    PrometheusController::class => [
        'class' => PrometheusController::class,
        '__construct()' => [
            'prometheusService' => Reference::to(PrometheusService::class)
        ]
    ],

    // Collector Registry Configuration
    CollectorRegistry::class => static function (): CollectorRegistry {
        $storageType = $_ENV['PROMETHEUS_STORAGE_TYPE'] ?? 'memory';
        
        return match ($storageType) {
            'redis' => new CollectorRegistry(
                new Redis([
                    'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
                    'port' => (int) ($_ENV['REDIS_PORT'] ?? 6379),
                    'timeout' => (float) ($_ENV['REDIS_TIMEOUT'] ?? 0.1),
                    'read_timeout' => (int) ($_ENV['REDIS_READ_TIMEOUT'] ?? 10),
                    'persistent_connections' => (bool) ($_ENV['REDIS_PERSISTENT']
                        ?? false),
                ])
            ),
            'apcu' => extension_loaded('apcu')
                    && class_exists('Prometheus\Storage\APC')
                ? new CollectorRegistry(new \Prometheus\Storage\APC())
                : new CollectorRegistry(new InMemory()),
            default => new CollectorRegistry(new InMemory())
        };
    },

    // Routes Configuration
    // Add these routes to your main routes configuration:
    /*
    Route::get('/metrics')
        ->action([PrometheusController::class, 'metrics'])
        ->name('prometheus/metrics'),
        
    Route::get('/prometheus/health')
        ->action([PrometheusController::class, 'health'])
        ->name('prometheus/health'),
        
    Route::get('/prometheus/dashboard')
        ->action([PrometheusController::class, 'dashboard'])
        ->name('prometheus/dashboard'),
    */
];
