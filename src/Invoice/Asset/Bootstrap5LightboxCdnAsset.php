<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * bs5-lightbox reads window.bootstrap.Modal/.Carousel synchronously at
 * script-parse time (not lazily on use), so BootstrapCdnJsOnlyAsset must be
 * declared here rather than relying on layout files registering Bootstrap
 * first — the AssetManager always inserts a bundle's $depends before the
 * bundle itself, regardless of registration call order elsewhere.
 */
class Bootstrap5LightboxCdnAsset extends AssetBundle
{
    public bool $cdn = true;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        '//cdn.jsdelivr.net/npm/bs5-lightbox@1.8.5/dist/index.bundle.min.js',
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js',
    ];

    public array $depends = [
        BootstrapCdnJsOnlyAsset::class,
    ];
}
