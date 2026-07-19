<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * bs5-lightbox reads window.bootstrap.Modal/.Carousel synchronously at
 * script-parse time (not lazily on use), so BootstrapJsOnlyAsset must be
 * declared here rather than relying on layout files registering Bootstrap
 * first — the AssetManager always inserts a bundle's $depends before the
 * bundle itself, regardless of registration call order elsewhere.
 */
class NodeModulesBootstrapLightboxAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bs5-lightbox/dist';

    public bool $cdn = false;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'index.bundle.min.js',
    ];

    public array $depends = [
        BootstrapJsOnlyAsset::class,
    ];
}
