<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class NodeModulesBootstrapIconsAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap-icons/font';

    public bool $cdn = false;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'bootstrap-icons.min.css',
    ];

    // Previously loaded async via cssOptions media="print" +
    // onload="this.media='all'" (a non-render-blocking CSS pattern), but
    // that onload is an inline event handler — CSP script-src dropping
    // 'unsafe-inline' silently blocked it, so the stylesheet's media never
    // switched back to 'all' and every bi-* icon on the site stopped
    // rendering. Loading it as a normal blocking stylesheet instead.
}
