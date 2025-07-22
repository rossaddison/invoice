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
        $time   = round($this->timer->get('overall'), 4);
        $memory = round(memory_get_peak_usage() / (1024 * 1024), 4);

        return 'Time: $time s. Memory: $memory mb.';
    }

    public static function opCacheHealthCheck(): string
    {
        $performanceItems = [];

        if (function_exists('opcache_get_status') && function_exists('opcache_get_configuration')) {
            $status                  = opcache_get_status();
            $statusOpcacheStatistics = [];
            if (is_array($status) && isset($status['opcache_statistics'])) {
                $statusOpcacheStatistics = (array) $status['opcache_statistics'];
            }
            $config                  = opcache_get_configuration();
            $configDirectives        = (array) $config['directives'];
            $cacheFull               = (bool) ($status['cache_full'] ?? false);
            $numCachedKeys           = (int) ($status['num_cached_keys'] ?? 0);
            $maxCachedKeys           = (int) ($configDirectives['opcache.max_accelerated_files'] ?? 0);
            $currentWastedPercentage = (float) ($status['current_wasted_percentage'] ?? 0.0);
            $maxWastedPercentage     = (float) ($configDirectives['opcache.max_wasted_percentage'] ?? 0.0);
            $opcacheHitRate          = (float) ($statusOpcacheStatistics['opcache_hit_rate'] ?? 0.0);
            $memoryConsumption       = (int) ($configDirectives['opcache.memory_consumption'] ?? 0);

            // Condition 1
            if ($cacheFull && ($currentWastedPercentage < $maxWastedPercentage)) {
                $performanceItems[] = 'Opcache: Cache full, wasted memory below threshold ({$currentWastedPercentage}% < {$maxWastedPercentage}%). '.
                    ($opcacheHitRate < 99 ? 'Hit rate dropped below 99% ({$opcacheHitRate}%). ' : '').
                    'Solution: Increase opcache.memory_consumption.';
            }
            // Condition 2
            if ($cacheFull && ($numCachedKeys == $maxCachedKeys)) {
                $performanceItems[] = 'Opcache: Cache full due to max files ({$maxCachedKeys}) reached. '.
                    'Solution: Increase opcache.max_accelerated_files.';
            }
            // Condition 3
            if (!$cacheFull && ($currentWastedPercentage >= $maxWastedPercentage)) {
                $performanceItems[] = 'Opcache: Frequent restarts, wasted memory exceeds threshold ({$currentWastedPercentage}% >= {$maxWastedPercentage}%). '.
                    'Solution: Increase opcache.max_wasted_percentage.';
            }
            // Condition 4
            if (
                isset($status['interned_strings_usage'])
                && isset($status['interned_strings_usage']['free_memory'])
                && isset($status['interned_strings_usage']['buffer_size'])
            ) {
                $bufferSize = (int) $status['interned_strings_usage']['buffer_size'];
                $freeMemory = (int) $status['interned_strings_usage']['free_memory'];
                // Calculate % free
                $freePercent = $bufferSize > 0 ? ($freeMemory / $bufferSize) * 100 : 100;
                // Dangerous if less than 10% free or less than 1MB free
                if ($freePercent < 10 || $freeMemory < 1024 * 1024) {
                    $performanceItems[] = 'Opcache: interned_strings_buffer is nearly full ('.
                        round($freePercent, 2).'% free, '.round($freeMemory / (1024 * 1024), 2).' MB left). '.
                        'Solution: Increase opcache.interned_strings_buffer in php.ini.';
                }
            }
            // Condition 5 Xdebug check and warning
            if (extension_loaded('xdebug')) {
                $performanceItems[] = 'Warning: Xdebug extension is enabled. This will disable OPcache optimizations and significantly reduce performance ❌.'.
                    'Solution: Disable Xdebug in production environments.';
            }

            // If no specific issues, show healthy status
            if (empty($performanceItems)) {
                $performanceItems[] = 'Opcache: Healthy configuration detected ✅.';
            }
        } else {
            $performanceItems[] = 'Opcache extension not available ❌.';
        }

        $performanceText = '';
        foreach ($performanceItems as $item) {
            $performanceText .= $item;
        }

        return $performanceText;
    }
}
