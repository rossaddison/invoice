<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * Bootstrap 5 CSS only — for layouts (main.php) where style.css is absent.
 * BootstrapAsset CSS is globally suppressed via customizedBundles; this bundle
 * provides Bootstrap CSS only where it is still needed.
 * sourcePath scoped to dist/css so its published hash differs from BootstrapAsset (dist/).
 */
final class BootstrapCssOnlyAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap/dist/css';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'bootstrap.min.css',
    ];
}
