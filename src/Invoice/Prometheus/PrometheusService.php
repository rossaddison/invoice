<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

/**
 * Prometheus Service for Yii3 Invoice Application
 *
 * Manages Prometheus metrics collection, storage, and rendering for integration
 * with Grafana, node_exporter, and windows_exporter monitoring stack.
 */
final class PrometheusService
{
    private CollectorRegistry $registry;
    private RenderTextFormat $renderer;
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->registry = $this->createRegistry();
        $this->renderer = new RenderTextFormat();
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    /**
     * Render metrics in Prometheus text format for /metrics endpoint
     */
    public function renderMetrics(): string
    {
        return $this->renderer->render($this->registry->getMetricFamilySamples());
    }

    /**
     * Get the correct Content-Type header for Prometheus metrics
     */
    public function getContentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }

    /**
     * Record business metrics for invoice operations
     */
    public function recordInvoiceOperation(string $operation, string $status): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'invoice_operations_total',
            'Total invoice operations',
            ['operation', 'status']
        );
        $counter->inc([$operation, $status]);
    }

    /**
     * Record product operations
     */
    public function recordProductOperation(string $operation, string $status): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'product_operations_total',
            'Total product operations',
            ['operation', 'status']
        );
        $counter->inc([$operation, $status]);
    }

    /**
     * Record family product generation (your custom feature)
     */
    public function recordFamilyProductGeneration(string $familyId, int $count = 1): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'products_generated_from_families_total',
            'Products generated from family comma lists',
            ['family_id']
        );
        $counter->incBy($count, [$familyId]);
    }

    /**
     * Update revenue metrics
     */
    public function updateRevenue(float $amount, string $currency, string $period = 'total'): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_business',
            'revenue_total',
            'Total revenue amount',
            ['currency', 'period']
        );
        $gauge->set($amount, [$currency, $period]);
    }

    /**
     * Update active user sessions
     */
    public function updateActiveUsers(int $count): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_users',
            'active_sessions',
            'Currently active user sessions'
        );
        $gauge->set($count);
    }

    /**
     * Record database query metrics
     */
    public function recordDatabaseQuery(string $table, string $operation, float $duration): void
    {
        $histogram = $this->registry->getOrRegisterHistogram(
            'yii3_invoice_db',
            'query_duration_seconds',
            'Database query execution time',
            ['table', 'operation'],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1.0]
        );
        $histogram->observe($duration, [$table, $operation]);
    }

    /**
     * Update database connection count
     */
    public function updateDatabaseConnections(int $count): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_db',
            'connections_active',
            'Active database connections'
        );
        $gauge->set($count);
    }

    /**
     * Set application health status
     */
    public function setApplicationHealth(bool $healthy): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'healthy',
            'Application health status (1=healthy, 0=unhealthy)'
        );
        $gauge->set($healthy ? 1 : 0);
    }

    /**
     * Update PHP memory usage
     */
    public function updateMemoryUsage(): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'memory_usage_bytes',
            'PHP memory usage in bytes'
        );
        $gauge->set(memory_get_usage(true));
    }

    /**
     * Windows-specific: Record IIS requests (for windows_exporter integration)
     */
    public function recordIISRequest(string $statusCode, string $method): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $counter = $this->registry->getOrRegisterCounter(
                'yii3_invoice_iis',
                'requests_total',
                'Total IIS requests for invoice application',
                ['status_code', 'method']
            );
            $counter->inc([$statusCode, $method]);
        }
    }

    /**
     * Windows-specific: Update service status
     */
    public function updateWindowsServiceStatus(string $serviceName, bool $running): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $gauge = $this->registry->getOrRegisterGauge(
                'yii3_invoice_service',
                'status',
                'Windows service status (1=running, 0=stopped)',
                ['service_name']
            );
            $gauge->set($running ? 1 : 0, [$serviceName]);
        }
    }

    /**
     * Create custom business counter
     * @param array<int, string> $labels
     */
    public function getOrCreateCounter(string $namespace, string $name, string $help, array $labels = []): Counter
    {
        return $this->registry->getOrRegisterCounter($namespace, $name, $help, $labels);
    }

    /**
     * Create custom business gauge
     * @param array<int, string> $labels
     */
    public function getOrCreateGauge(string $namespace, string $name, string $help, array $labels = []): Gauge
    {
        return $this->registry->getOrRegisterGauge($namespace, $name, $help, $labels);
    }

    /**
     * Create custom business histogram
     * @param array<int, string> $labels
     * @param array<int, float>|null $buckets
     */
    public function getOrCreateHistogram(string $namespace, string $name, string $help, array $labels = [], ?array $buckets = null): Histogram
    {
        return $this->registry->getOrRegisterHistogram(
            $namespace,
            $name,
            $help,
            $labels,
            $buckets ?? [0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );
    }

    /**
     * Initialize application build info metric
     */
    public function initializeBuildInfo(string $version, string $phpVersion, string $yiiVersion): void
    {
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'build_info',
            'Application build information',
            ['version', 'php_version', 'yii_version']
        );
        $gauge->set(1, [$version, $phpVersion, $yiiVersion]);
    }

    public function performHealthCheck(): array
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => time(),
            'checks' => [
                'memory' => [],
                'metrics_registry' => [
                    'status' => 'unknown',
                    'storage_type' => null,
                ],
            ],
        ];

        try {
            // Check memory usage
            $memoryUsage = memory_get_usage(true);
            $memoryLimitValue = ini_get('memory_limit');
            $memoryLimit = $memoryLimitValue !== false ? $this->parseBytes($memoryLimitValue) : PHP_INT_MAX;
            $memoryPercent = ($memoryUsage / $memoryLimit) * 100;
            
            $health['checks']['memory'] = [
                'status' => $memoryPercent > 80 ? 'warning' : 'ok',
                'usage_bytes' => $memoryUsage,
                'limit_bytes' => $memoryLimit,
                'usage_percent' => round($memoryPercent, 2)
            ];

            $configStorage = (array) $this->config['storage'];

            // Check if metrics registry is working
            $health['checks']['metrics_registry'] = [
                'status' => 'ok',
                'storage_type' => $configStorage['type'] ?? 'unknown'
            ];

            // Set overall health status
            $hasWarnings = false;
            foreach ($health['checks'] as $check) {
                $status = $check['status'] ?? 'unknown';
                if (in_array($status, ['warning', 'error'], true)) {
                    $hasWarnings = true;
                    break;
                }
            }

            if ($hasWarnings) {
                $health['status'] = 'warning';
            }

            // Update health metric
            $this->setApplicationHealth($health['status'] === 'healthy');

        } catch (\Throwable $e) {
            $health['status'] = 'error';
            $health['error'] = $e->getMessage();
            $this->setApplicationHealth(false);
        }

        return $health;
    }

    private function createRegistry(): CollectorRegistry
    {
        // Related logic: config/common/prometheus.php
        $config = $this->config;
        $configStorage = (array) $config['storage'];
        $configStorageType = (string) $configStorage['type'];
        $configStorageRedis = (array) $configStorage['redis'];
        $storageType = $configStorageType ?: 'memory';

        return match ($storageType) {
            'redis' => new CollectorRegistry(new Redis($configStorageRedis ?: [])),
            'apcu' => new CollectorRegistry(new APC()),
            default => new CollectorRegistry(new InMemory())
        };
    }

    private function getDefaultConfig(): array
    {
        return [
            'storage' => [
                'type' => 'memory', // 'memory', 'redis', or 'apcu'
                'redis' => [
                    'host' => '127.0.0.1',
                    'port' => 6379,
                    'timeout' => 0.1,
                    'read_timeout' => 10,
                    'persistent_connections' => false
                ]
            ],
            'namespace_prefix' => 'yii3_invoice',
            'enabled' => true
        ];
    }

    private function parseBytes(string $val): int
    {
        $val = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val = (int) $val;

        return match ($last) {
            'g' => $val * 1024 * 1024 * 1024,
            'm' => $val * 1024 * 1024,
            'k' => $val * 1024,
            default => $val
        };
    }
}