<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * Bootstrap 5 JS bundle only — CSS is already included in style.css.
 * sourcePath scoped to dist/js so its published hash differs from BootstrapAsset (dist/).
 */
final class BootstrapJsOnlyAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap/dist/js';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'bootstrap.bundle.js',
    ];
}
