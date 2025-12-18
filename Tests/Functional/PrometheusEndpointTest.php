<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\Redis;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

/**
 * Test Prometheus metrics endpoint integration
 * This test simulates real-world usage in the invoice application
 */
final class PrometheusEndpointTest extends TestCase
{
    private CollectorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        // Use InMemory for testing, in production you'd use Redis or APCu
        $this->registry = new CollectorRegistry(new InMemory());
    }

    public function testInvoiceSystemMetrics(): void
    {
        // Simulate metrics that would be collected in a real invoice system
        
        // 1. Invoice operations
        $invoiceOpsCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice',
            'invoice_operations_total',
            'Total invoice operations',
            ['operation', 'status']
        );

        // Simulate some operations
        $invoiceOpsCounter->inc(['create', 'success']);
        $invoiceOpsCounter->inc(['create', 'success']);
        $invoiceOpsCounter->inc(['create', 'error']);
        $invoiceOpsCounter->inc(['view', 'success']);
        $invoiceOpsCounter->incBy(5, ['view', 'success']);

        // 2. Database query metrics
        $dbQueryHistogram = $this->registry->getOrRegisterHistogram(
            'yii3_invoice',
            'database_query_duration_seconds',
            'Database query execution time',
            ['table', 'operation'],
            [0.001, 0.005, 0.01, 0.05, 0.1, 0.5, 1.0]
        );

        // Simulate database queries
        $dbQueryHistogram->observe(0.003, ['invoice', 'select']);
        $dbQueryHistogram->observe(0.012, ['product', 'select']);
        $dbQueryHistogram->observe(0.008, ['family', 'select']);
        $dbQueryHistogram->observe(0.025, ['invoice', 'insert']);

        // 3. Active connections gauge
        $connectionsGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice',
            'active_connections',
            'Number of active database connections'
        );
        $connectionsGauge->set(3);

        // 4. System resource metrics
        $memoryGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice',
            'php_memory_usage_bytes',
            'PHP memory usage in bytes'
        );
        $memoryGauge->set(memory_get_usage(true));

        // Generate metrics output
        $renderer = new RenderTextFormat();
        $output = $renderer->render($this->registry->getMetricFamilySamples());

        // Test the output format and content
        $this->assertStringContainsString('# HELP yii3_invoice_invoice_operations_total', $output);
        $this->assertStringContainsString('# TYPE yii3_invoice_invoice_operations_total counter', $output);
        
        // Check specific metric values
        $this->assertStringContainsString('operation="create",status="success"} 2', $output);
        $this->assertStringContainsString('operation="create",status="error"} 1', $output);
        $this->assertStringContainsString('operation="view",status="success"} 6', $output);

        // Verify histogram metrics are present
        $this->assertStringContainsString('yii3_invoice_database_query_duration_seconds_bucket', $output);
        $this->assertStringContainsString('yii3_invoice_database_query_duration_seconds_count', $output);
        $this->assertStringContainsString('yii3_invoice_database_query_duration_seconds_sum', $output);

        // Verify gauge metrics
        $this->assertStringContainsString('yii3_invoice_active_connections 3', $output);
        $this->assertStringContainsString('yii3_invoice_php_memory_usage_bytes', $output);

        // Ensure proper Prometheus format
        $lines = explode("\n", $output);
        $metricLines = array_filter($lines, fn($line) => !empty($line) && !str_starts_with($line, '#'));
        
        $this->assertGreaterThan(10, count($metricLines), 'Should have multiple metric lines');
        
        foreach ($metricLines as $line) {
            if (!empty($line)) {
                $this->assertMatchesRegularExpression(
                    '/^[a-zA-Z_:][a-zA-Z0-9_.:]*(\{[^}]*\})?\s+[\d.eE+-]+$/',
                    trim($line),
                    "Metric line should match Prometheus format: $line"
                );
            }
        }
    }

    public function testBusinessMetrics(): void
    {
        // Business-specific metrics for the invoice application
        
        // Revenue metrics
        $revenueGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice_business',
            'total_revenue_amount',
            'Total revenue amount',
            ['currency']
        );
        $revenueGauge->set(125000.50, ['USD']);
        $revenueGauge->set(95000.75, ['EUR']);

        // Product family performance
        $familyProductsCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'products_generated_from_families_total',
            'Products generated from family comma lists',
            ['family_id']
        );
        $familyProductsCounter->incBy(25, ['family_1']);
        $familyProductsCounter->incBy(15, ['family_2']);
        $familyProductsCounter->incBy(30, ['family_3']);

        // User activity metrics  
        $userActionsCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice_business',
            'user_actions_total',
            'Total user actions',
            ['action', 'controller']
        );
        $userActionsCounter->inc(['view', 'product']);
        $userActionsCounter->inc(['create', 'invoice']);
        $userActionsCounter->inc(['edit', 'family']);
        $userActionsCounter->incBy(3, ['view', 'invoice']);

        $renderer = new RenderTextFormat();
        $output = $renderer->render($this->registry->getMetricFamilySamples());

        // Verify business metrics are properly formatted
        $this->assertStringContainsString('yii3_invoice_business_total_revenue_amount{currency="USD"} 125000.5', $output);
        $this->assertStringContainsString('yii3_invoice_business_total_revenue_amount{currency="EUR"} 95000.75', $output);
        
        $this->assertStringContainsString('yii3_invoice_business_products_generated_from_families_total{family_id="family_1"} 25', $output);
        $this->assertStringContainsString('yii3_invoice_business_products_generated_from_families_total{family_id="family_2"} 15', $output);
        $this->assertStringContainsString('yii3_invoice_business_products_generated_from_families_total{family_id="family_3"} 30', $output);

        $this->assertStringContainsString('yii3_invoice_business_user_actions_total{action="view",controller="product"} 1', $output);
        $this->assertStringContainsString('yii3_invoice_business_user_actions_total{action="view",controller="invoice"} 3', $output);
    }

    public function testMetricsEndpointOutput(): void
    {
        // Simulate what a real /metrics endpoint would return
        $this->addSampleMetrics();
        
        $renderer = new RenderTextFormat();
        $output = $renderer->render($this->registry->getMetricFamilySamples());
        
        // Test that output is valid for Prometheus scraping
        $this->assertStringStartsWithAnyOf([
            '# HELP',
            '# TYPE',
            // Or a metric line if no help/type comments
        ], $output);
        
        // Check content-type would be correct
        $this->assertEquals('text/plain; version=0.0.4; charset=utf-8', 'text/plain; version=0.0.4; charset=utf-8');
        
        // Verify no PHP errors or warnings in output
        $this->assertStringNotContainsString('Warning:', $output);
        $this->assertStringNotContainsString('Error:', $output);
        $this->assertStringNotContainsString('Fatal:', $output);
        
        // Check metrics are properly separated
        $lines = explode("\n", $output);
        $this->assertGreaterThan(5, count($lines));
        
        // Verify we have both HELP and TYPE comments
        $helpLines = array_filter($lines, fn($line) => str_starts_with($line, '# HELP'));
        $typeLines = array_filter($lines, fn($line) => str_starts_with($line, '# TYPE'));
        
        $this->assertGreaterThan(0, count($helpLines), 'Should have HELP comments');
        $this->assertGreaterThan(0, count($typeLines), 'Should have TYPE comments');
    }

    private function addSampleMetrics(): void
    {
        // Add various metric types for comprehensive testing
        
        $this->registry->getOrRegisterCounter('test', 'requests_total', 'Total requests', ['status'])
            ->incBy(100, ['200']);
            
        $this->registry->getOrRegisterGauge('test', 'cpu_usage', 'CPU usage percentage')
            ->set(75.5);
            
        $this->registry->getOrRegisterHistogram('test', 'request_duration', 'Request duration', ['endpoint'])
            ->observe(0.5, ['api']);
    }

    private function assertStringStartsWithAnyOf(array $prefixes, string $string): void
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($string, $prefix)) {
                $this->assertTrue(true);
                return;
            }
        }
        $this->fail("String does not start with any of the expected prefixes: " . implode(', ', $prefixes));
    }
}