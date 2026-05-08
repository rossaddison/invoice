<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

/**
 * Test Prometheus integration with basic metrics
 */
final class PrometheusIntegrationTest extends TestCase
{
    private CollectorRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CollectorRegistry(new InMemory());
    }

    public function testBasicCounterMetric(): void
    {
        // Create a counter for invoice creation
        $counter = $this->registry->getOrRegisterCounter(
            'yii3_invoice',
            'invoices_created_total',
            'Total number of invoices created',
            ['status']
        );

        // Increment counter for different statuses
        $counter->incBy(5, ['draft']);
        $counter->incBy(3, ['sent']);
        $counter->incBy(2, ['paid']);

        // Get metrics
        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        // Assert metrics are present
        $this->assertStringContainsString('yii3_invoice_invoices_created_total', $result);
        $this->assertStringContainsString('status="draft"', $result);
        $this->assertStringContainsString('status="sent"', $result);
        $this->assertStringContainsString('status="paid"', $result);
        
        // Check values
        $this->assertStringContainsString('} 5', $result); // draft count
        $this->assertStringContainsString('} 3', $result); // sent count
        $this->assertStringContainsString('} 2', $result); // paid count
    }

    public function testGaugeMetric(): void
    {
        // Create a gauge for active user sessions
        $gauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice',
            'active_users',
            'Number of currently active users'
        );

        // Set gauge value
        $gauge->set(42);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        $this->assertStringContainsString('yii3_invoice_active_users', $result);
        $this->assertStringContainsString('42', $result);
    }

    public function testHistogramMetric(): void
    {
        // Create histogram for HTTP request duration
        $histogram = $this->registry->getOrRegisterHistogram(
            'yii3_invoice',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'endpoint'],
            [0.1, 0.5, 1.0, 2.5, 5.0, 10.0]
        );

        // Record some durations
        $histogram->observe(0.3, ['GET', '/invoice/index']);
        $histogram->observe(1.2, ['POST', '/invoice/add']);
        $histogram->observe(0.8, ['GET', '/product/view']);

        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        $this->assertStringContainsString('yii3_invoice_http_request_duration_seconds', $result);
        $this->assertStringContainsString('method="GET"', $result);
        $this->assertStringContainsString('method="POST"', $result);
        $this->assertStringContainsString('endpoint="/invoice/index"', $result);
    }

    public function testMultipleMetricsExport(): void
    {
        // Create multiple metrics to simulate real application
        $invoiceCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice',
            'invoices_total',
            'Total invoices in system'
        );

        $productCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice',
            'products_total',
            'Total products in system'
        );

        $familyCounter = $this->registry->getOrRegisterCounter(
            'yii3_invoice',
            'families_total',
            'Total product families in system'
        );

        $memoryGauge = $this->registry->getOrRegisterGauge(
            'yii3_invoice',
            'memory_usage_bytes',
            'Current memory usage in bytes'
        );

        // Set some values
        $invoiceCounter->incBy(150);
        $productCounter->incBy(1200);
        $familyCounter->incBy(45);
        $memoryGauge->set(memory_get_usage());

        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        // Verify all metrics are present
        $this->assertStringContainsString('yii3_invoice_invoices_total', $result);
        $this->assertStringContainsString('yii3_invoice_products_total', $result);
        $this->assertStringContainsString('yii3_invoice_families_total', $result);
        $this->assertStringContainsString('yii3_invoice_memory_usage_bytes', $result);

        // Verify values
        $this->assertStringContainsString('150', $result);
        $this->assertStringContainsString('1200', $result);
        $this->assertStringContainsString('45', $result);
    }

    public function testMetricsFormat(): void
    {
        $counter = $this->registry->getOrRegisterCounter(
            'app',
            'test_counter',
            'A test counter'
        );

        $counter->inc();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        // Check Prometheus format compliance
        $this->assertStringContainsString('# HELP app_test_counter A test counter', $result);
        $this->assertStringContainsString('# TYPE app_test_counter counter', $result);
        $this->assertStringContainsString('app_test_counter 1', $result);
    }
}
