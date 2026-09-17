<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Invoice\Prometheus\PrometheusService;
use PHPUnit\Framework\TestCase;

/**
 * Regression test for the storage-type default that made Prometheus
 * metrics silently no-op in production.
 *
 * Root cause: PrometheusService's default config, and
 * config/common/di/prometheus.php's CollectorRegistry::class factory,
 * both fell back to Prometheus\Storage\InMemory when PROMETHEUS_STORAGE_TYPE
 * was unset. InMemory only lives inside the current PHP process -- on
 * PHP-FPM/Apache every request is a fresh process with nothing shared, so
 * a Prometheus scrape hitting /prometheus/metrics only ever saw that one
 * scrape request's own (near-empty) data, never real traffic. Fixed by
 * defaulting to 'apcu' instead, since this app already requires ext-apcu.
 *
 * What this test can and can't prove: apc.enable_cli is off by default
 * for the CLI SAPI this test suite runs under (it's a PHP_INI_SYSTEM
 * directive, not changeable at runtime via ini_set() -- confirmed
 * directly), so PrometheusService::createRegistry()'s own extension_loaded()
 * && apcu_enabled() guard will still fall back to InMemory here even
 * with the fix in place. That's expected and correct -- the actual bug
 * was the *default config value*, which this test verifies directly via
 * performHealthCheck()'s reported storage_type, independent of whether
 * APCu happens to be active in whatever process is asking. Real
 * cross-request persistence under PHP-FPM/Apache (where apc.enabled, not
 * apc.enable_cli, is what matters) can only be confirmed by hitting
 * /prometheus/metrics twice on a live deployment.
 */
final class PrometheusStorageDefaultTest extends TestCase
{
    public function testDefaultStorageTypeIsApcuNotMemory(): void
    {
        $service = new PrometheusService();

        $health = $service->performHealthCheck();

        $this->assertSame('apcu', $health['checks']['metrics_registry']['storage_type']);
    }

    public function testExplicitStorageTypeOverrideStillWins(): void
    {
        // 'memory' explicitly requested still works -- the default changed,
        // not the ability to opt back into it (e.g. for local dev without
        // APCu configured).
        $service = new PrometheusService(['storage' => ['type' => 'memory']]);

        $health = $service->performHealthCheck();

        $this->assertSame('memory', $health['checks']['metrics_registry']['storage_type']);
    }
}
