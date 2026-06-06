<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;
use Prometheus\Storage\APC;
use Prometheus\RenderTextFormat;

/**
 * Prometheus Service for Yii3 Invoice Application.
 *
 * Manages registry lifecycle, metrics rendering, and health checks.
 * Metric recording is delegated to {@see PrometheusMetricsRecorder}.
 */
final class PrometheusService
{
    private CollectorRegistry $registry;
    private RenderTextFormat $renderer;
    private array $config;
    private PrometheusMetricsRecorder $recorder;

    public function __construct(array $config = [])
    {
        $this->config   = array_merge($this->getDefaultConfig(), $config);
        $this->registry = $this->createRegistry();
        $this->renderer = new RenderTextFormat();
        $this->recorder = new PrometheusMetricsRecorder($this->registry);
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    public function getRecorder(): PrometheusMetricsRecorder
    {
        return $this->recorder;
    }

    public function renderMetrics(): string
    {
        return $this->renderer->render($this->registry->getMetricFamilySamples());
    }

    public function getContentType(): string
    {
        return RenderTextFormat::MIME_TYPE;
    }

    public function performHealthCheck(): array
    {
        $health = [
            'status'    => 'healthy',
            'timestamp' => time(),
            'checks'    => [
                'memory'           => [],
                'metrics_registry' => ['status' => 'unknown', 'storage_type' => null],
            ],
        ];

        try {
            $memoryUsage    = memory_get_usage(true);
            $memLimitVal    = ini_get('memory_limit');
            $memoryLimit    = $memLimitVal !== false ? $this->parseBytes($memLimitVal) : PHP_INT_MAX;
            $memoryPercent  = ($memoryUsage / $memoryLimit) * 100;

            $health['checks']['memory'] = [
                'status'        => $memoryPercent > 80 ? 'warning' : 'ok',
                'usage_bytes'   => $memoryUsage,
                'limit_bytes'   => $memoryLimit,
                'usage_percent' => round($memoryPercent, 2),
            ];

            $configStorage = (array) $this->config['storage'];
            $health['checks']['metrics_registry'] = [
                'status'       => 'ok',
                'storage_type' => $configStorage['type'] ?? 'unknown',
            ];

            $hasWarnings = false;
            foreach ($health['checks'] as $check) {
                if (in_array($check['status'] ?? 'unknown', ['warning', 'error'], true)) {
                    $hasWarnings = true;
                    break;
                }
            }

            if ($hasWarnings) {
                $health['status'] = 'warning';
            }

            $this->recorder->setApplicationHealth($health['status'] === 'healthy');

        } catch (\Throwable $e) {
            $health['status'] = 'error';
            $health['error']  = $e->getMessage();
            $this->recorder->setApplicationHealth(false);
        }

        return $health;
    }

    private function createRegistry(): CollectorRegistry
    {
        $configStorage     = (array) $this->config['storage'];
        $configStorageType = (string) $configStorage['type'];
        $configStorageRedis = (array) $configStorage['redis'];
        $storageType       = $configStorageType ?: 'memory';

        return match ($storageType) {
            'redis' => new CollectorRegistry(new Redis($configStorageRedis ?: [])),
            'apcu'  => extension_loaded('apcu') && class_exists(APC::class) && apcu_enabled()
                ? new CollectorRegistry(new APC())
                : new CollectorRegistry(new InMemory()),
            default => new CollectorRegistry(new InMemory()),
        };
    }

    private function getDefaultConfig(): array
    {
        return [
            'storage' => [
                'type'  => 'memory',
                'redis' => [
                    'host'                 => '127.0.0.1',
                    'port'                 => 6379,
                    'timeout'              => 0.1,
                    'read_timeout'         => 10,
                    'persistent_connections' => false,
                ],
            ],
            'namespace_prefix' => 'yii3_invoice',
            'enabled'          => true,
        ];
    }

    private function parseBytes(string $val): int
    {
        $val  = trim($val);
        $last = strtolower($val[strlen($val) - 1]);
        $val  = (int) $val;

        return match ($last) {
            'g' => $val * 1024 * 1024 * 1024,
            'm' => $val * 1024 * 1024,
            'k' => $val * 1024,
            default => $val,
        };
    }
}
