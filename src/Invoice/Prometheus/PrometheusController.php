<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use App\Invoice\BaseController;
use App\Invoice\Prometheus\PrometheusService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Session\SessionInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Prometheus Metrics Controller
 *
 * Exposes /metrics endpoint for Prometheus scraping and provides
 * health check endpoint for monitoring integration.
 */
final class PrometheusController extends BaseController
{
    protected string $controllerName = 'invoice/prometheus';

    public function __construct(
        private PrometheusService $prometheusService,
        private ResponseFactoryInterface $psr17ResponseFactory,
        private StreamFactoryInterface $streamFactory,
        SessionInterface $session,
        sR $sR,
        TranslatorInterface $translator,
        UserService $userService,
        WebViewRenderer $webViewRenderer,
        WebControllerService $webService,
        Flash $flash,
    ) {
        parent::__construct($webService, $userService, $translator, $webViewRenderer, $session, $sR, $flash);
    }

    /**
     * Prometheus metrics endpoint
     *
     * This endpoint is scraped by Prometheus server to collect metrics.
     * Compatible with Grafana dashboards and integrates with node_exporter
     * and windows_exporter metrics.
     * @return Response
     */
    public function metrics(): Response
    {
        // Suppress display_errors so vendor deprecation warnings (e.g. PHP 8.5
        // $http_response_header in digitalbazaar/json-ld) don't corrupt the
        // Prometheus text format that scrapers parse line-by-line.
        $prev = ini_set('display_errors', '0');

        try {
            $this->updateSystemMetrics();
            $metricsOutput = $this->prometheusService->renderMetrics();

            return $this->rawTextResponse($metricsOutput, $this->prometheusService->getContentType())
                ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->withHeader('Pragma', 'no-cache')
                ->withHeader('Expires', '0');

        } catch (\Throwable) {
            $this->prometheusService->setApplicationHealth(false);

            return $this->rawTextResponse($this->getErrorMetrics(), $this->prometheusService->getContentType(), 500);
        } finally {
            if ($prev !== false) {
                ini_set('display_errors', $prev);
            }
        }
    }

    /**
     * Health check endpoint for monitoring
     * Returns detailed health information in JSON format.
     * Can be used by monitoring systems to check application status.
     * @return Response
     */
    public function health(): Response
    {
        try {
            $healthData = $this->prometheusService->performHealthCheck();

            if (isset($healthData['checks']) && is_array($healthData['checks'])) {
                $healthData['checks']['database'] = $this->checkDatabaseHealth();
                $healthData['checks']['file_permissions'] = $this->checkFilePermissions();
            }

            $statusCode = match ($healthData['status']) {
                'healthy', 'warning' => 200,
                default => 503,
            };

            return $this->rawTextResponse(
                (string) json_encode($healthData, JSON_PRETTY_PRINT),
                'application/json',
                $statusCode,
            );

        } catch (\Throwable $e) {
            $errorHealth = [
                'status' => 'error',
                'timestamp' => time(),
                'error' => $e->getMessage(),
                'checks' => [],
            ];

            return $this->rawTextResponse(
                (string) json_encode($errorHealth),
                'application/json',
                503,
            );
        }
    }

    private function rawTextResponse(string $body, string $contentType, int $status = 200): Response
    {
        return $this->psr17ResponseFactory
            ->createResponse($status)
            ->withHeader('Content-Type', $contentType)
            ->withBody($this->streamFactory->createStream($body));
    }

    /**
     * Prometheus dashboard — returns combined health + system info as JSON.
     * Displayed via the Performance dropdown in the invoice layout.
     * @return Response
     */
    public function dashboard(): Response
    {
        try {
            $healthData = $this->prometheusService->performHealthCheck();

            if (isset($healthData['checks']) && is_array($healthData['checks'])) {
                $healthData['checks']['database'] = $this->checkDatabaseHealth();
                $healthData['checks']['file_permissions'] = $this->checkFilePermissions();
            }

            $healthData['system'] = $this->getSystemInfo();
            $healthData['metrics_url'] = '/metrics';
            $healthData['health_url'] = '/prometheus/health';

            $statusCode = match ($healthData['status']) {
                'healthy', 'warning' => 200,
                default => 503,
            };

            return $this->rawTextResponse(
                (string) json_encode($healthData, JSON_PRETTY_PRINT),
                'application/json',
                $statusCode,
            );
        } catch (\Throwable $e) {
            return $this->rawTextResponse(
                (string) json_encode(['status' => 'error', 'error' => $e->getMessage()]),
                'application/json',
                503,
            );
        }
    }

    private function updateSystemMetrics(): void
    {
        // Update PHP memory usage
        $this->prometheusService->updateMemoryUsage();

        // Update database connections (mock - you'd implement actual DB connection counting)
        $this->prometheusService->updateDatabaseConnections($this->getActiveConnectionCount());

        // Update active user sessions
        $this->prometheusService->updateActiveUsers($this->getActiveUserCount());

        // Initialize build info if not already set
        static $buildInfoInitialized = false;
        if (!$buildInfoInitialized) {
            $this->prometheusService->initializeBuildInfo('1.0.0', PHP_VERSION, '3.0');
            $buildInfoInitialized = true;
        }

        // Windows-specific metrics
        if (PHP_OS_FAMILY === 'Windows') {
            $this->updateWindowsMetrics();
        }
    }

    private function updateWindowsMetrics(): void
    {
        // Update Windows service status (examples)
        $services = ['mysql', 'apache', 'redis'];
        foreach ($services as $service) {
            $isRunning = $this->checkWindowsService($service);
            $this->prometheusService->updateWindowsServiceStatus($service, $isRunning);
        }
    }

    private function getActiveConnectionCount(): int
    {
        // Mock implementation - replace with actual database connection counting
        // You might query INFORMATION_SCHEMA.PROCESSLIST or use your DB abstraction layer
        return random_int(3, 8);
    }

    private function getActiveUserCount(): int
    {
        // Mock implementation - replace with actual session counting
        // You might count active sessions from your session storage
        return random_int(5, 25);
    }

    private function checkWindowsService(string $_serviceName): bool
    {
        // Mock implementation - replace with actual Windows service checking
        // You might use `sc query` command or Windows API
        return true;
    }

    private function checkDatabaseHealth(): array
    {
        try {
            // Mock implementation - replace with actual database health check
            // You might execute a simple SELECT query to verify connectivity

            return [
                'status' => 'ok',
                'connections' => $this->getActiveConnectionCount(),
                'response_time_ms' => random_int(5, 25)
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    private function checkFilePermissions(): array
    {
        $paths = [
            'runtime' => dirname(__DIR__, 3) . '/runtime',
            'public' => dirname(__DIR__, 3) . '/public',
            'logs' => dirname(__DIR__, 3) . '/runtime/logs'
        ];

        $results = [];
        foreach ($paths as $name => $path) {
            $results[$name] = [
                'path' => $path,
                'exists' => is_dir($path),
                'writable' => is_writable($path),
                'status' => (is_dir($path) && is_writable($path)) ? 'ok' : 'error'
            ];
        }

        $allOk = true;
        foreach ($results as $result) {
            if ($result['status'] === 'error') {
                $allOk = false;
                break;
            }
        }

        return [
            'status' => $allOk ? 'ok' : 'error',
            'paths' => $results
        ];
    }

    private function getErrorMetrics(): string
    {
        // Return minimal error metrics when the main metrics collection fails
        return "# HELP yii3_invoice_application_healthy Application health status\n" .
               "# TYPE yii3_invoice_application_healthy gauge\n" .
               "yii3_invoice_application_healthy 0\n" .
               "# HELP yii3_invoice_errors_total Total application errors\n" .
               "# TYPE yii3_invoice_errors_total counter\n" .
               "yii3_invoice_errors_total{type=\"metrics_collection\"} 1\n";
    }

    private function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'os' => PHP_OS_FAMILY,
            'memory_limit' => ini_get('memory_limit'),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'server_time' => date('Y-m-d H:i:s'),
            'uptime_seconds' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
        ];
    }
}