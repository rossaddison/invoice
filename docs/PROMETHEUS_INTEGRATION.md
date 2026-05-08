# Prometheus Integration for Yii3 Invoice Application

This document describes the Prometheus monitoring integration with support for Grafana dashboards, node_exporter, and windows_exporter.

## Overview

The Prometheus integration provides comprehensive monitoring for the Yii3 invoice application with:

- **HTTP Request Metrics**: Request count, duration, status codes
- **Business Metrics**: Invoice operations, product generation, revenue tracking
- **System Metrics**: PHP memory usage, database connections, application health
- **Platform Metrics**: Windows-specific metrics for IIS and services

## Components

### 1. PrometheusMiddleware
**File**: `src/Invoice/Middleware/PrometheusMiddleware.php`

Automatically collects HTTP request metrics for every request:
- Request duration histograms with optimized buckets for web applications
- Request counters by method, path, status code, and controller
- In-progress request tracking
- Application health monitoring

### 2. PrometheusService
**File**: `src/Invoice/Service/PrometheusService.php`

Central service for managing metrics:
- Registry management with multiple storage backends (InMemory, Redis, APCu)
- Business metric recording methods
- Health check functionality
- Windows-specific metric collection

### 3. PrometheusController
**File**: `src/Invoice/Prometheus/PrometheusController.php`

Exposes monitoring endpoints:
- `/prometheus/metrics` - Prometheus scraping endpoint
- `/prometheus/health` - JSON health check endpoint  
- `/prometheus/dashboard` - Web interface for viewing metrics

### 4. Configuration
**File**: `config/common/prometheus.php`

Dependency injection configuration with environment-based settings.

## Installation

### 1. Install Prometheus Client Library

```bash
composer require promphp/prometheus_client_php
```

### 2. Add Configuration

Add the prometheus configuration to your DI container:

```php
// config/common/params.php or di configuration
return array_merge(
    require __DIR__ . '/prometheus.php',
    // ... other configurations
);
```

### 3. Add Routes

Add these routes to your route configuration:

```php
use App\Invoice\Prometheus\PrometheusController;

// In your routes file
Route::get('/metrics')
    ->action([PrometheusController::class, 'metrics'])
    ->name('prometheus/metrics');

Route::get('/prometheus/health')  
    ->action([PrometheusController::class, 'health'])
    ->name('prometheus/health');

Route::get('/prometheus/dashboard')
    ->action([PrometheusController::class, 'dashboard'])  
    ->name('prometheus/dashboard');
```

### 4. Add Middleware

Register the PrometheusMiddleware in your middleware stack:

```php
// In your application configuration
use App\Invoice\Middleware\PrometheusMiddleware;

$application->add(PrometheusMiddleware::class);
```

### 5. Environment Configuration

Copy the environment variables from `config/.env.prometheus` to your `.env` file.

## Storage Options

### Development (InMemory)
```env
PROMETHEUS_STORAGE_TYPE=memory
```
Metrics are lost between requests. Good for development and testing.

### Production (Redis)
```env  
PROMETHEUS_STORAGE_TYPE=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```
Persistent metrics storage. Recommended for production.

### Shared Hosting (APCu)
```env
PROMETHEUS_STORAGE_TYPE=apcu
```
Uses PHP's APCu cache. Good when Redis is not available.

## Integration with Monitoring Stack

### Prometheus Configuration

Add this job to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'yii3-invoice-app'
    static_configs:
      - targets: ['localhost:8080']  # Your application URL
    metrics_path: '/metrics'
    scrape_interval: 15s
    scrape_timeout: 10s
```

### Node Exporter Integration

The application metrics complement node_exporter metrics:

1. Install node_exporter on Linux/Unix systems
2. Configure to run on port 9100
3. Add to Prometheus configuration:

```yaml
  - job_name: 'node-exporter'
    static_configs:
      - targets: ['localhost:9100']
```

### Windows Exporter Integration

For Windows systems, use windows_exporter:

1. Install windows_exporter
2. Configure to run on port 9182  
3. Add to Prometheus configuration:

```yaml
  - job_name: 'windows-exporter'
    static_configs:
      - targets: ['localhost:9182']
```

### Grafana Dashboards

The metrics are compatible with rfmoz/grafana-dashboards:

1. Import standard dashboards from rfmoz/grafana-dashboards
2. Create custom panels for business metrics:
   - `yii3_invoice_business_revenue_total`
   - `yii3_invoice_business_invoice_operations_total`
   - `yii3_invoice_business_products_generated_from_families_total`

## Metrics Reference

### HTTP Metrics
- `yii3_invoice_http_requests_total` - Total HTTP requests
- `yii3_invoice_http_request_duration_seconds` - Request duration histogram
- `yii3_invoice_http_requests_in_progress` - Current requests being processed

### Business Metrics  
- `yii3_invoice_business_invoice_operations_total` - Invoice operations (create, edit, view)
- `yii3_invoice_business_product_operations_total` - Product operations
- `yii3_invoice_business_products_generated_from_families_total` - Family product generation
- `yii3_invoice_business_revenue_total` - Revenue by currency and period
- `yii3_invoice_users_active_sessions` - Active user sessions

### System Metrics
- `yii3_invoice_application_healthy` - Application health (1=healthy, 0=unhealthy)
- `yii3_invoice_php_memory_usage_bytes` - PHP memory usage
- `yii3_invoice_db_connections_active` - Active database connections
- `yii3_invoice_db_query_duration_seconds` - Database query duration

### Windows Metrics (Windows only)
- `yii3_invoice_iis_requests_total` - IIS request count
- `yii3_invoice_service_status` - Windows service status
- `yii3_invoice_temp_files_count` - Temporary files count

## Usage Examples

### Recording Custom Business Metrics

```php
use App\Invoice\Prometheus\PrometheusService;

class InvoiceController 
{
    public function __construct(private PrometheusService $prometheus) {}
    
    public function create(): Response 
    {
        try {
            // Create invoice logic...
            $this->prometheus->recordInvoiceOperation('create', 'success');
        } catch (\Exception $e) {
            $this->prometheus->recordInvoiceOperation('create', 'error');
            throw $e;
        }
    }
}
```

### Recording Family Product Generation

```php
// In your FamilyController generate_products method
$generatedCount = count($createdProducts);
$this->prometheus->recordFamilyProductGeneration(
    (string) $family->getFamily_id(), 
    $generatedCount
);
```

### Updating Revenue Metrics

```php
// Update monthly revenue
$this->prometheus->updateRevenue(50000.00, 'USD', 'monthly');
```

## Monitoring Dashboard

Visit `/prometheus/dashboard` to see:
- Application health status
- System information  
- Metrics overview with counts by type
- Detailed metric values
- Integration information

## Troubleshooting

### Metrics Not Appearing
1. Check that middleware is registered
2. Verify routes are configured
3. Check storage configuration (Redis connection, APCu enabled)
4. Review application logs for errors

### Performance Impact
- InMemory storage: Minimal impact, metrics lost between requests
- Redis storage: Small network overhead per request
- APCu storage: Minimal impact, shared between requests

### High Cardinality
Avoid creating metrics with too many unique label combinations:
- ✅ Good: `{method="GET", status="200"}`  
- ❌ Bad: `{user_id="123", session_id="abc123"}` (unique per user)

## Security Considerations

### Metrics Endpoint Security
The `/metrics` endpoint exposes application metrics. Consider:
- Restricting access to monitoring networks only
- Using authentication for sensitive metrics
- Filtering sensitive labels from metrics

### Example Nginx Configuration
```nginx
location /metrics {
    allow 10.0.0.0/8;     # Internal monitoring network
    allow 172.16.0.0/12;   # Docker networks  
    deny all;
    
    proxy_pass http://backend;
}
```

This integration provides comprehensive monitoring for your Yii3 invoice application with seamless integration into the Prometheus/Grafana ecosystem.