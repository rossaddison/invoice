<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;

/**
 * Records all business, infrastructure, and platform-specific Prometheus metrics.
 *
 * Extracted from PrometheusService to keep class size under S1448 threshold.
 * Owns all getOrRegister* interactions with the CollectorRegistry.
 */
final class PrometheusMetricsRecorder
{
    public function __construct(
        private readonly CollectorRegistry $registry,
    ) {}

    public function recordInvoiceOperation(string $operation, string $status): void
    {
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'invoice_operations_total',
            'Total invoice operations',
            ['operation', 'status']
        )->inc([$operation, $status]);
    }

    public function recordProductOperation(string $operation, string $status): void
    {
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'product_operations_total',
            'Total product operations',
            ['operation', 'status']
        )->inc([$operation, $status]);
    }

    public function recordFamilyProductGeneration(string $familyId, int $count = 1): void
    {
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'products_generated_from_families_total',
            'Products generated from family comma lists',
            ['family_id']
        )->incBy($count, [$familyId]);
    }

    public function updateRevenue(float $amount, string $currency, string $period = 'total'): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_business',
            'revenue_total',
            'Total revenue amount',
            ['currency', 'period']
        )->set($amount, [$currency, $period]);
    }

    public function updateActiveUsers(int $count): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_users',
            'active_sessions',
            'Currently active user sessions'
        )->set($count);
    }

    public function recordDatabaseQuery(string $table, string $operation, float $duration): void
    {
        $this->registry->getOrRegisterHistogram(
            'yii3_invoice_db',
            'query_duration_seconds',
            'Database query execution time',
            ['table', 'operation'],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1.0]
        )->observe($duration, [$table, $operation]);
    }

    public function updateDatabaseConnections(int $count): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_db',
            'connections_active',
            'Active database connections'
        )->set($count);
    }

    public function setApplicationHealth(bool $healthy): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'healthy',
            'Application health status (1=healthy, 0=unhealthy)'
        )->set($healthy ? 1 : 0);
    }

    public function updateMemoryUsage(): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'memory_usage_bytes',
            'PHP memory usage in bytes'
        )->set(memory_get_usage(true));
    }

    public function recordIISRequest(string $statusCode, string $method): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->registry->getOrRegisterCounter(
                'yii3_invoice_iis',
                'requests_total',
                'Total IIS requests for invoice application',
                ['status_code', 'method']
            )->inc([$statusCode, $method]);
        }
    }

    public function updateWindowsServiceStatus(string $serviceName, bool $running): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->registry->getOrRegisterGauge(
                'yii3_invoice_service',
                'status',
                'Windows service status (1=running, 0=stopped)',
                ['service_name']
            )->set($running ? 1 : 0, [$serviceName]);
        }
    }

    /** @param array<int, string> $labels */
    public function getOrCreateCounter(string $namespace, string $name, string $help, array $labels = []): Counter
    {
        return $this->registry->getOrRegisterCounter($namespace, $name, $help, $labels);
    }

    /** @param array<int, string> $labels */
    public function getOrCreateGauge(string $namespace, string $name, string $help, array $labels = []): Gauge
    {
        return $this->registry->getOrRegisterGauge($namespace, $name, $help, $labels);
    }

    /**
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

    public function initializeBuildInfo(string $version, string $phpVersion, string $yiiVersion): void
    {
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'build_info',
            'Application build information',
            ['version', 'php_version', 'yii_version']
        )->set(1, [$version, $phpVersion, $yiiVersion]);
    }
}
