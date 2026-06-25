<?php

declare(strict_types=1);

namespace App\Widget;

use App\Timer;
use Yiisoft\Widget\Widget;

final class PerformanceMetrics extends Widget
{
    public function __construct(private readonly Timer $timer)
    {
    }

    #[\Override]
    public function render(): string
    {
        $time = round($this->timer->get('overall'), 4);
        $memory = round(memory_get_peak_usage() / (1024 * 1024), 4);

        return 'Time: ' . $time . ' s. Memory: ' . $memory . ' mb.';
    }

    public static function prometheusStatus(): string
    {
        $parts = [];

        $apcuOk = extension_loaded('apcu') && function_exists('apcu_enabled') && apcu_enabled();
        $parts[] = 'Prometheus APCu: ' . ($apcuOk ? '✅' : '❌');

        $memMb = round(memory_get_usage(true) / (1_048_576), 2);
        $peakMb = round(memory_get_peak_usage(true) / (1_048_576), 2);
        $parts[] = "Mem: {$memMb} MB / Peak: {$peakMb} MB";

        return implode(' | ', $parts);
    }

    public static function opCacheHealthCheck(): string
    {
        if (!function_exists('opcache_get_status') || !function_exists('opcache_get_configuration')) {
            return 'Opcache extension not available ❌.';
        }
        $status = opcache_get_status();
        $config = opcache_get_configuration();
        $configDirectives = (array) $config['directives'];
        $performanceItems = self::buildPerformanceItems($status, $configDirectives);
        if (empty($performanceItems)) {
            $performanceItems[] = 'Opcache: Healthy configuration detected ✅.';
        }
        $performanceText = '';
        foreach ($performanceItems as $item) {
            $performanceText .= $item;
        }
        return $performanceText;
    }

    /** @return list<string> */
    private static function buildPerformanceItems(array|false $status, array $configDirectives): array
    {
        $items = [];
        if (!is_array($status)) {
            return $items;
        }
        $statusOpcacheStatistics = isset($status['opcache_statistics'])
            ? (array) $status['opcache_statistics'] : [];
        $cacheFull = (bool) ($status['cache_full'] ?? false);
        $numCachedKeys = (int) ($status['num_cached_keys'] ?? 0);
        $maxCachedKeys = (int) ($configDirectives['opcache.max_accelerated_files'] ?? 0);
        $currentWastedPercentage = (float) ($status['current_wasted_percentage'] ?? 0.0);
        $maxWastedPercentage = (float) ($configDirectives['opcache.max_wasted_percentage'] ?? 0.0);
        $opcacheHitRate = (float) ($statusOpcacheStatistics['opcache_hit_rate'] ?? 0.0);
        $memoryConsumption = (int) ($configDirectives['opcache.memory_consumption'] ?? 0);

        if ($cacheFull && ($currentWastedPercentage < $maxWastedPercentage)) {
            $items[] = 'Opcache: Cache full, wasted memory below threshold ({$currentWastedPercentage}% < {$maxWastedPercentage}%). '
                . ($opcacheHitRate < 99 ? 'Hit rate dropped below 99% ({$opcacheHitRate}%). ' : '')
                . 'Solution: Increase opcache.memory_consumption from ' . $memoryConsumption;
        }
        if ($cacheFull && ($numCachedKeys == $maxCachedKeys)) {
            $items[] = 'Opcache: Cache full due to max files ({$maxCachedKeys}) reached. '
                . 'Solution: Increase opcache.max_accelerated_files.';
        }
        if (!$cacheFull && ($currentWastedPercentage >= $maxWastedPercentage)) {
            $items[] = 'Opcache: Frequent restarts, wasted memory exceeds threshold ({$currentWastedPercentage}% >= {$maxWastedPercentage}%). '
                . 'Solution: Increase opcache.max_wasted_percentage.';
        }
        $internedIssue = self::checkInternedStringsBuffer($status);
        if ($internedIssue !== null) {
            $items[] = $internedIssue;
        }
        if (extension_loaded('xdebug')) {
            $items[] = 'Warning: Xdebug extension is enabled. This will disable OPcache optimizations and significantly reduce performance ❌.'
                . 'Solution: Disable Xdebug in production environments.';
        }
        return $items;
    }

    private static function checkInternedStringsBuffer(array $status): ?string
    {
        if (
            !isset($status['interned_strings_usage']['free_memory'])
            || !isset($status['interned_strings_usage']['buffer_size'])
        ) {
            return null;
        }
        $bufferSize = (int) $status['interned_strings_usage']['buffer_size'];
        $freeMemory = (int) $status['interned_strings_usage']['free_memory'];
        $freePercent = $bufferSize > 0 ? ($freeMemory / $bufferSize) * 100 : 100;
        if ($freePercent < 10 || $freeMemory < 1024 * 1024) {
            return 'Opcache: interned_strings_buffer is nearly full ('
                . round($freePercent, 2) . '% free, ' . round($freeMemory / (1024 * 1024), 2) . ' MB left). '
                . 'Solution: Increase opcache.interned_strings_buffer in php.ini.';
        }
        return null;
    }
}
