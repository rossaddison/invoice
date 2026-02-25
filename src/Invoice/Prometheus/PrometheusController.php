<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use App\Invoice\BaseController;
use App\Invoice\Prometheus\PrometheusService;
use App\Service\WebControllerService;
use App\User\UserService;
use App\Invoice\Setting\SettingRepository as sR;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
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
    protected string $controllerName = 'prometheus';

    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private PrometheusService $prometheusService,
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
        try {
            // Update system metrics before rendering
            $this->updateSystemMetrics();
            
            // Get metrics in Prometheus text format
            $metricsOutput = $this->prometheusService->renderMetrics();
            
            // Create response with proper Prometheus content type
            $response = $this->responseFactory->createResponse($metricsOutput);
            
            return $response->withHeader('Content-Type', $this->prometheusService->getContentType())
                           ->withHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                           ->withHeader('Pragma', 'no-cache')
                           ->withHeader('Expires', '0');
                           
        } catch (\Throwable $e) {
            // Set application as unhealthy and return error
            $this->prometheusService->setApplicationHealth(false);
            
            $errorMetrics = $this->getErrorMetrics($e);
            
            return $this->responseFactory->createResponse($errorMetrics, 500)
                                        ->withHeader('Content-Type', $this->prometheusService->getContentType());
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
            
            // Add additional application-specific health checks
            if (isset($healthData['checks']) && is_array($healthData['checks'])) {
                $healthData['checks']['database'] = $this->checkDatabaseHealth();
                $healthData['checks']['file_permissions'] = $this->checkFilePermissions();
            }
            
            $statusCode = match ($healthData['status']) {
                'healthy' => 200,
                'warning' => 200,
                'error' => 503,
                default => 503
            };
            
            return $this->responseFactory->createResponse(json_encode($healthData, JSON_PRETTY_PRINT), $statusCode)
                                        ->withHeader('Content-Type', 'application/json');
                                        
        } catch (\Throwable $e) {
            $errorHealth = [
                'status' => 'error',
                'timestamp' => time(),
                'error' => $e->getMessage(),
                'checks' => []
            ];
            
            return $this->responseFactory->createResponse(json_encode($errorHealth), 503)
                                        ->withHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Metrics dashboard endpoint (optional)
     *
     * Provides a simple web interface to view current metrics.
     * Useful for development and debugging.
     * @return Response
     */
    public function dashboard(): Response
    {
        $healthData = $this->prometheusService->performHealthCheck();
        $metricsOutput = $this->prometheusService->renderMetrics();
        
        // Parse metrics for display
        $parsedMetrics = $this->parseMetricsForDisplay($metricsOutput);
        
        $parameters = [
            'title' => 'Prometheus Metrics Dashboard',
            'health' => $healthData,
            'metrics' => $parsedMetrics,
            'system_info' => $this->getSystemInfo(),
            'refresh_url' => '/prometheus/dashboard',
            'metrics_url' => '/prometheus/metrics',
            'health_url' => '/prometheus/health',
        ];
        
        return $this->webViewRenderer->render('dashboard', $parameters);
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
        return rand(3, 8);
    }

    private function getActiveUserCount(): int
    {
        // Mock implementation - replace with actual session counting
        // You might count active sessions from your session storage
        return rand(5, 25);
    }

    private function checkWindowsService(string $serviceName): bool
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
                'response_time_ms' => rand(5, 25)
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

    private function getErrorMetrics(\Throwable $e): string
    {
        // Return minimal error metrics when the main metrics collection fails
        return "# HELP yii3_invoice_application_healthy Application health status\n" .
               "# TYPE yii3_invoice_application_healthy gauge\n" .
               "yii3_invoice_application_healthy 0\n" .
               "# HELP yii3_invoice_errors_total Total application errors\n" .
               "# TYPE yii3_invoice_errors_total counter\n" .
               "yii3_invoice_errors_total{type=\"metrics_collection\"} 1\n";
    }

    private function parseMetricsForDisplay(string $metricsOutput): array
    {
        $lines = explode("\n", $metricsOutput);
        $metrics = [];
        $currentMetric = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            if (str_starts_with($line, '# HELP ')) {
                $parts = explode(' ', $line, 4);
                $metricName = $parts[2] ?? 'unknown';
                $help = $parts[3] ?? '';
                $currentMetric = $metricName;
                $metrics[$currentMetric] = [
                    'name' => $metricName,
                    'help' => $help,
                    'type' => 'unknown',
                    'values' => []
                ];
            } elseif (str_starts_with($line, '# TYPE ')) {
                $parts = explode(' ', $line, 4);
                $metricName = $parts[2] ?? 'unknown';
                $type = $parts[3] ?? 'unknown';
                if (isset($metrics[$metricName])) {
                    $metrics[$metricName]['type'] = $type;
                }
            } elseif (!str_starts_with($line, '#')) {
                // Metric value line
                if ($currentMetric !== null && isset($metrics[$currentMetric])) {
                    $metrics[$currentMetric]['values'][] = $line;
                }
            }
        }
        
        return $metrics;
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