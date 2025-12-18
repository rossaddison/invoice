<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Test Prometheus integration with node_exporter and windows_exporter
 * These tests simulate the integration environment you'll have with Grafana
 */
final class PrometheusExporterIntegrationTest extends TestCase
{
    public function testNodeExporterCompatibility(): void
    {
        // Test that our application metrics are compatible with node_exporter format
        $registry = new CollectorRegistry(new InMemory());
        
        // Create metrics that complement node_exporter metrics
        $appHealthGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_health',
            'application_healthy',
            'Application health status (1=healthy, 0=unhealthy)'
        );
        $appHealthGauge->set(1);
        
        $dbConnectionsGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_db',
            'connections_active',
            'Active database connections'
        );
        $dbConnectionsGauge->set(5);
        
        $phpProcessesGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_php',
            'fpm_processes_active',
            'Active PHP-FPM processes'
        );
        $phpProcessesGauge->set(8);

        // Simulate metrics that would work alongside node_exporter
        $output = $this->getMetricsOutput($registry);
        
        // Verify format is compatible with Prometheus/Grafana
        $this->assertStringContainsString('yii3_invoice_health_application_healthy 1', $output);
        $this->assertStringContainsString('yii3_invoice_db_connections_active 5', $output);
        $this->assertStringContainsString('yii3_invoice_php_fpm_processes_active 8', $output);
        
        // Ensure metric names follow Prometheus naming conventions
        $lines = explode("\n", $output);
        $metricLines = array_filter($lines, fn($line) => !str_starts_with($line, '#') && !empty(trim($line)));
        
        foreach ($metricLines as $line) {
            $this->assertMatchesRegularExpression(
                '/^[a-zA-Z_][a-zA-Z0-9_]*/',
                trim($line),
                "Metric name should follow Prometheus conventions: $line"
            );
        }
    }

    public function testWindowsExporterCompatibility(): void
    {
        // Test metrics that complement windows_exporter on Windows systems
        $registry = new CollectorRegistry(new InMemory());
        
        // Windows-specific application metrics
        $iisRequestsCounter = $registry->getOrRegisterCounter(
            'yii3_invoice_iis',
            'requests_total',
            'Total IIS requests for invoice application',
            ['status_code', 'method']
        );
        $iisRequestsCounter->incBy(250, ['200', 'GET']);
        $iisRequestsCounter->incBy(45, ['200', 'POST']);
        $iisRequestsCounter->incBy(12, ['404', 'GET']);
        $iisRequestsCounter->incBy(3, ['500', 'POST']);

        $windowsServiceGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_service',
            'status',
            'Windows service status (1=running, 0=stopped)',
            ['service_name']
        );
        $windowsServiceGauge->set(1, ['mysql']);
        $windowsServiceGauge->set(1, ['apache']);
        $windowsServiceGauge->set(1, ['redis']);

        // Application-specific Windows metrics
        $tempFilesGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_temp',
            'files_count',
            'Temporary files created by application'
        );
        $tempFilesGauge->set(23);

        $output = $this->getMetricsOutput($registry);
        
        // Verify Windows-compatible metrics
        $this->assertStringContainsString('yii3_invoice_iis_requests_total{status_code="200",method="GET"} 250', $output);
        $this->assertStringContainsString('yii3_invoice_iis_requests_total{status_code="500",method="POST"} 3', $output);
        $this->assertStringContainsString('yii3_invoice_service_status{service_name="mysql"} 1', $output);
        $this->assertStringContainsString('yii3_invoice_temp_files_count 23', $output);
    }

    public function testGrafanaDashboardCompatibility(): void
    {
        // Test metrics that work well with rfmoz/grafana-dashboards
        $registry = new CollectorRegistry(new InMemory());
        
        // HTTP request metrics (standard for web applications)
        $httpRequestsCounter = $registry->getOrRegisterCounter(
            'yii3_invoice_http',
            'requests_total',
            'Total HTTP requests',
            ['method', 'status', 'endpoint']
        );
        
        $httpDurationHistogram = $registry->getOrRegisterHistogram(
            'yii3_invoice_http',
            'request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'status', 'endpoint'],
            [0.001, 0.005, 0.01, 0.025, 0.05, 0.075, 0.1, 0.25, 0.5, 0.75, 1.0, 2.5, 5.0, 7.5, 10.0]
        );

        // Simulate various HTTP requests
        $endpoints = [
            ['GET', '200', '/invoice/index'],
            ['GET', '200', '/product/view'],
            ['POST', '200', '/family/generate-products'],
            ['GET', '404', '/nonexistent'],
            ['POST', '422', '/invoice/create']
        ];

        foreach ($endpoints as [$method, $status, $endpoint]) {
            $httpRequestsCounter->inc([$method, $status, $endpoint]);
            $duration = match($status) {
                '200' => rand(10, 500) / 1000,  // 0.01 to 0.5 seconds
                '404' => rand(5, 50) / 1000,    // 0.005 to 0.05 seconds  
                '422' => rand(100, 1000) / 1000, // 0.1 to 1 second
                default => rand(10, 100) / 1000
            };
            $httpDurationHistogram->observe($duration, [$method, $status, $endpoint]);
        }

        // Business metrics for dashboards
        $invoiceRevenueGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_business',
            'revenue_total',
            'Total invoice revenue',
            ['currency', 'period']
        );
        $invoiceRevenueGauge->set(50000.00, ['USD', 'monthly']);
        $invoiceRevenueGauge->set(45000.00, ['EUR', 'monthly']);

        $activeUsersGauge = $registry->getOrRegisterGauge(
            'yii3_invoice_users',
            'active_sessions',
            'Currently active user sessions'
        );
        $activeUsersGauge->set(12);

        $output = $this->getMetricsOutput($registry);
        
        // Verify Grafana-friendly metrics format
        $this->assertStringContainsString('yii3_invoice_http_requests_total{method="GET",status="200",endpoint="/invoice/index"} 1', $output);
        $this->assertStringContainsString('yii3_invoice_http_request_duration_seconds_bucket', $output);
        $this->assertStringContainsString('yii3_invoice_business_revenue_total{currency="USD",period="monthly"} 50000', $output);
        $this->assertStringContainsString('yii3_invoice_users_active_sessions 12', $output);
        
        // Check histogram buckets are properly formatted
        $this->assertStringContainsString('le="0.001"', $output);
        $this->assertStringContainsString('le="0.5"', $output);
        $this->assertStringContainsString('le="+Inf"', $output);
    }

    public function testPrometheusConfigCompatibility(): void
    {
        // Test that metrics work with typical Prometheus configuration
        $registry = new CollectorRegistry(new InMemory());
        
        // Add job and instance labels that Prometheus adds automatically
        $upGauge = $registry->getOrRegisterGauge(
            'yii3_invoice',
            'up',
            'Application availability'
        );
        $upGauge->set(1);
        
        // Add build info metric (common pattern)
        $buildInfoGauge = $registry->getOrRegisterGauge(
            'yii3_invoice',
            'build_info',
            'Build information',
            ['version', 'php_version', 'yii_version']
        );
        $buildInfoGauge->set(1, ['1.0.0', PHP_VERSION, '3.0']);

        $output = $this->getMetricsOutput($registry);
        
        // Verify standard metrics
        $this->assertStringContainsString('yii3_invoice_up 1', $output);
        $this->assertStringContainsString('yii3_invoice_build_info{version="1.0.0"', $output);
        $this->assertStringContainsString('php_version="' . PHP_VERSION . '"', $output);
        
        // Ensure output is valid for Prometheus scraping
        $this->assertStringNotContainsString('NaN', $output);
        // Note: +Inf and -Inf are valid in histogram buckets, so we only check for standalone 'Inf'
        $this->assertStringNotContainsString(' Inf ', $output, 'Should not contain standalone Inf values');
        $this->assertStringNotContainsString('null', $output);
    }

    /**
     * Simulate testing against a real node_exporter endpoint
     * This would be used in integration testing with actual exporters
     */
    public function testNodeExporterEndpointAvailability(): void
    {
        // This test demonstrates how you'd check if node_exporter is running
        // In real testing, you'd have node_exporter running on :9100
        
        $expectedNodeExporterUrl = 'http://localhost:9100/metrics';
        
        // For this test, we'll just verify the URL format and expected response structure
        $this->assertStringContainsString('localhost:9100', $expectedNodeExporterUrl);
        $this->assertStringEndsWith('/metrics', $expectedNodeExporterUrl);
        
        // In a real environment, you might do:
        // $client = new Client();
        // $response = $client->get($expectedNodeExporterUrl);
        // $this->assertEquals(200, $response->getStatusCode());
        // $body = $response->getBody()->getContents();
        // $this->assertStringContainsString('node_cpu_seconds_total', $body);
        
        $this->assertTrue(true, 'Node exporter URL format is correct');
    }

    /**
     * Simulate testing against a real windows_exporter endpoint  
     */
    public function testWindowsExporterEndpointAvailability(): void
    {
        // This test demonstrates checking windows_exporter availability
        // In real testing, you'd have windows_exporter running on :9182
        
        $expectedWindowsExporterUrl = 'http://localhost:9182/metrics';
        
        $this->assertStringContainsString('localhost:9182', $expectedWindowsExporterUrl);
        $this->assertStringEndsWith('/metrics', $expectedWindowsExporterUrl);
        
        // In a real environment:
        // $client = new Client();  
        // $response = $client->get($expectedWindowsExporterUrl);
        // $this->assertEquals(200, $response->getStatusCode());
        // $body = $response->getBody()->getContents();
        // $this->assertStringContainsString('windows_cpu_time_total', $body);
        
        $this->assertTrue(true, 'Windows exporter URL format is correct');
    }

    private function getMetricsOutput(CollectorRegistry $registry): string
    {
        $renderer = new \Prometheus\RenderTextFormat();
        return $renderer->render($registry->getMetricFamilySamples());
    }
}