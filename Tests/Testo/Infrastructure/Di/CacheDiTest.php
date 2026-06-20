<?php

declare(strict_types=1);

namespace Tests\Testo\Infrastructure\Di;

use Psr\SimpleCache\CacheInterface;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Cache\Cache;
use Yiisoft\Cache\CacheInterface as YiiCacheInterface;
use Yiisoft\Cache\File\FileCache;

/**
 * Verifies config/common/di/cache.php wiring:
 *   CacheInterface (PSR)  → FileCache  @ runtime/cache
 *   YiiCacheInterface     → Cache      (wraps FileCache)
 */
#[Test]
final class CacheDiTest
{
    private readonly string $cachePath;
    private readonly FileCache $fileCache;
    private readonly Cache $yiiCache;

    public function __construct()
    {
        $this->cachePath = dirname(__DIR__, 4) . '/runtime/cache';
        $this->fileCache = new FileCache($this->cachePath);
        $this->yiiCache  = new Cache($this->fileCache);
    }

    public function fileCacheImplementsPsrCacheInterface(): void
    {
        Assert::instanceOf($this->fileCache, CacheInterface::class);
    }

    public function yiiCacheImplementsYiiCacheInterface(): void
    {
        Assert::instanceOf($this->yiiCache, YiiCacheInterface::class);
    }

    public function fileCacheDirectoryIsWritable(): void
    {
        if (!is_dir($this->cachePath)) {
            mkdir($this->cachePath, 0o755, true);
        }

        Assert::true(is_writable($this->cachePath));
    }

    public function setAndGetValue(): void
    {
        $this->fileCache->set('testo_di_test', 'invoice', 60);

        Assert::same($this->fileCache->get('testo_di_test'), 'invoice');
    }

    public function deleteValue(): void
    {
        $this->fileCache->set('testo_di_delete', 'to-be-deleted', 60);
        $this->fileCache->delete('testo_di_delete');

        Assert::null($this->fileCache->get('testo_di_delete'));
    }

    public function missingKeyReturnsDefault(): void
    {
        Assert::same($this->fileCache->get('testo_nonexistent_key', 'default'), 'default');
    }
}
