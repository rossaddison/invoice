<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * Bootstrap 5 JS bundle served via CDN — CSS is already included in style.css.
 * Loading only the JS avoids the double-load of Bootstrap 5 CSS.
 */
final class BootstrapCdnJsOnlyAsset extends AssetBundle
{
    public bool $cdn = true;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
            'integrity' => 'sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI',
            'crossorigin' => 'anonymous',
        ],
    ];
}
