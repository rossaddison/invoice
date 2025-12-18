<?php

declare(strict_types=1);

namespace App\Invoice\Prometheus;

use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Histogram;
use Prometheus\Gauge;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Prometheus Metrics Middleware for Yii3 Invoice Application
 * 
 * Automatically collects HTTP request metrics for monitoring with Prometheus, Grafana, 
 * node_exporter, and windows_exporter integration.
 */
final class PrometheusMiddleware implements MiddlewareInterface
{
    private CollectorRegistry $registry;
    private Counter $httpRequestsCounter;
    private Histogram $httpRequestDurationHistogram;
    private Gauge $httpRequestsInProgressGauge;
    private Gauge $applicationHealthGauge;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
        $this->initializeMetrics();
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        $method = $request->getMethod();
        $uri = $request->getUri();
        $path = $this->normalizePath($uri->getPath());
        
        // Track requests in progress
        $this->httpRequestsInProgressGauge->inc();
        
        // Set application health (1 = healthy)
        $this->applicationHealthGauge->set(1);
        
        try {
            // Process the request
            $response = $handler->handle($request);
            $statusCode = (string) $response->getStatusCode();
            
            // Record successful request metrics
            $this->recordRequestMetrics($method, $path, $statusCode, $start);
            
            return $response;
            
        } catch (\Throwable $e) {
            // Record error metrics
            $statusCode = '500';
            $this->recordRequestMetrics($method, $path, $statusCode, $start);
            
            // Set application health to unhealthy
            $this->applicationHealthGauge->set(0);
            
            throw $e;
        } finally {
            // Decrement in-progress requests
            $this->httpRequestsInProgressGauge->dec();
        }
    }

    private function initializeMetrics(): void
    {
        // HTTP Requests Total Counter
        $this->httpRequestsCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_http',
            'requests_total',
            'Total number of HTTP requests',
            ['method', 'path', 'status_code', 'controller']
        );

        // HTTP Request Duration Histogram
        $this->httpRequestDurationHistogram = $this->registry->getOrRegisterHistogram(
            'yii3_invoice_http',
            'request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'path', 'status_code', 'controller'],
            // Buckets optimized for web application response times
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );

        // HTTP Requests In Progress Gauge
        $this->httpRequestsInProgressGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_http',
            'requests_in_progress',
            'Current number of HTTP requests being processed'
        );

        // Application Health Gauge
        $this->applicationHealthGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'healthy',
            'Application health status (1=healthy, 0=unhealthy)'
        );

        // Business-specific metrics for invoice application
        $this->initializeBusinessMetrics();
        
        // System metrics that complement node_exporter and windows_exporter
        $this->initializeSystemMetrics();
    }

    private function initializeBusinessMetrics(): void
    {
        // Invoice operations counter
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'invoice_operations_total',
            'Total invoice operations',
            ['operation', 'status']
        );

        // Product operations counter
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'product_operations_total',
            'Total product operations',
            ['operation', 'status']
        );

        // Family product generation counter (from your family commalist feature)
        $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'products_generated_from_families_total',
            'Products generated from family comma lists',
            ['family_id']
        );

        // Revenue tracking gauge
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_business',
            'revenue_total',
            'Total revenue amount',
            ['currency', 'period']
        );

        // Active user sessions gauge
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_users',
            'active_sessions',
            'Currently active user sessions'
        );
    }

    private function initializeSystemMetrics(): void
    {
        // Database metrics
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_db',
            'connections_active',
            'Active database connections'
        );

        $this->registry->getOrRegisterHistogram(
            'yii3_invoice_db',
            'query_duration_seconds',
            'Database query execution time',
            ['table', 'operation'],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1.0]
        );

        // PHP metrics
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'memory_usage_bytes',
            'PHP memory usage in bytes'
        );

        $this->registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'fpm_processes_active',
            'Active PHP-FPM processes'
        );

        // Application build info
        $this->registry->getOrRegisterGauge(
            'yii3_invoice_application',
            'build_info',
            'Application build information',
            ['version', 'php_version', 'yii_version']
        );

        // Windows-specific metrics (for windows_exporter integration)
        if (PHP_OS_FAMILY === 'Windows') {
            $this->registry->getOrRegisterCounter(
                'yii3_invoice_iis',
                'requests_total',
                'Total IIS requests for invoice application',
                ['status_code', 'method']
            );

            $this->registry->getOrRegisterGauge(
                'yii3_invoice_service',
                'status',
                'Windows service status (1=running, 0=stopped)',
                ['service_name']
            );

            $this->registry->getOrRegisterGauge(
                'yii3_invoice_temp',
                'files_count',
                'Temporary files created by application'
            );
        }
    }

    private function recordRequestMetrics(string $method, string $path, string $statusCode, float $start): void
    {
        $duration = microtime(true) - $start;
        $controller = $this->extractControllerFromPath($path);
        
        $labels = [$method, $path, $statusCode, $controller];
        
        // Record request count
        $this->httpRequestsCounter->inc($labels);
        
        // Record request duration
        $this->httpRequestDurationHistogram->observe($duration, $labels);
        
        // Update system metrics
        $this->updateSystemMetrics();
        
        // Update business metrics based on the request
        $this->updateBusinessMetrics($path, $statusCode);
    }

    private function updateSystemMetrics(): void
    {
        // Update PHP memory usage
        $memoryGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'memory_usage_bytes',
            'PHP memory usage in bytes'
        );
        $memoryGauge->set(memory_get_usage(true));

        // Update application build info (only set once)
        static $buildInfoSet = false;
        if (!$buildInfoSet) {
            $buildInfoGauge = $this->registry->getOrRegisterGauge(
                'yii3_invoice_application',
                'build_info',
                'Application build information',
                ['version', 'php_version', 'yii_version']
            );
            $buildInfoGauge->set(1, ['1.0.0', PHP_VERSION, '3.0']);
            $buildInfoSet = true;
        }

        // Windows-specific metrics
        if (PHP_OS_FAMILY === 'Windows') {
            $tempFilesGauge = $this->registry->getOrRegisterGauge(
                'yii3_invoice_temp',
                'files_count',
                'Temporary files created by application'
            );
            // Count temp files (example implementation)
            $tempDir = sys_get_temp_dir();
            $tempFileCount = iterator_count(new \FilesystemIterator($tempDir, \FilesystemIterator::SKIP_DOTS));
            $tempFilesGauge->set($tempFileCount);
        }
    }

    private function updateBusinessMetrics(string $path, string $statusCode): void
    {
        $success = in_array($statusCode, ['200', '201', '202']) ? 'success' : 'error';
        
        // Track invoice operations
        if (str_contains($path, '/invoice/')) {
            $invoiceOpsCounter = $this->registry->getOrRegisterCounter(
                'yii3_invoice_business',
                'invoice_operations_total',
                'Total invoice operations',
                ['operation', 'status']
            );
            
            $operation = $this->extractOperationFromPath($path);
            $invoiceOpsCounter->inc([$operation, $success]);
        }
        
        // Track product operations  
        if (str_contains($path, '/product/')) {
            $productOpsCounter = $this->registry->getOrRegisterCounter(
                'yii3_invoice_business',
                'product_operations_total',
                'Total product operations',
                ['operation', 'status']
            );
            
            $operation = $this->extractOperationFromPath($path);
            $productOpsCounter->inc([$operation, $success]);
        }
        
        // Track family product generation (your custom feature)
        if (str_contains($path, '/family/generate-products')) {
            $familyProductsCounter = $this->registry->getOrRegisterCounter(
                'yii3_invoice_business',
                'products_generated_from_families_total',
                'Products generated from family comma lists',
                ['family_id']
            );
            // Note: You would need to extract family_id from request or session
            $familyProductsCounter->inc(['unknown']);
        }
    }

    private function normalizePath(string $path): string
    {
        
        // Remove numeric IDs and normalize paths for better grouping
        $normalizedPath = preg_replace('/\/\d+/', '/{id}', $path);
        if ($normalizedPath === null) {
            $normalizedPath = $path;
        }
        
        // Handle common patterns in your invoice application
        $result = preg_replace('/\/product\/\{id\}\/view/', '/product/{id}/view', $normalizedPath);
        $normalizedPath = $result !== null ? $result : $normalizedPath;
        
        $result = preg_replace('/\/invoice\/\{id\}\/edit/', '/invoice/{id}/edit', $normalizedPath);
        $normalizedPath = $result !== null ? $result : $normalizedPath;
        
        $result = preg_replace('/\/family\/\{id\}/', '/family/{id}', $normalizedPath);
        $normalizedPath = $result !== null ? $result : $normalizedPath;
        
        return $normalizedPath !== '' ? $normalizedPath : '/';
    }

    private function extractControllerFromPath(string $path): string
    {
        // Extract controller name from path
        $segments = explode('/', trim($path, '/'));
        
        if (count($segments) >= 2) {
            return $segments[0] . '/' . $segments[1];
        }
        
        if (count($segments) >= 1 && $segments[0] !== '') {
            return $segments[0];
        }
        
        return 'unknown';
    }

    private function extractOperationFromPath(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        
        // Get the last segment as operation (e.g., /invoice/inv/create -> create)
        if (count($segments) >= 3) {
            return $segments[2];
        }
        
        // Default operations
        if (count($segments) >= 2) {
            return $segments[1];
        }
        
        return 'view';
    }
}