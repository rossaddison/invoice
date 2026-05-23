<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * Bootstrap 5 CSS served via CDN — for layouts (main.php) where style.css is absent.
 * BootstrapCdnAsset CSS is globally suppressed via customizedBundles; this bundle
 * provides Bootstrap CSS only where it is still needed.
 */
final class BootstrapCdnCssOnlyAsset extends AssetBundle
{
    public bool $cdn = true;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        [
            'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
            'integrity' => 'sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB',
            'crossorigin' => 'anonymous',
        ],
    ];
}
