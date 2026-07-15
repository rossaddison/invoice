<?php

declare(strict_types=1);

namespace App\Asset;

use Yiisoft\Assets\AssetBundle;

final class AppCdnAsset extends AssetBundle
{
    public bool $cdn = true;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        '//cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css',
    ];

    // Previously loaded async via cssOptions media="print" +
    // onload="this.media='all'" — that onload is an inline event handler,
    // silently blocked once CSP script-src dropped 'unsafe-inline', so the
    // stylesheet's media never switched back to 'all' and every bi-* icon
    // stopped rendering. Loading it as a normal blocking stylesheet instead.
}
