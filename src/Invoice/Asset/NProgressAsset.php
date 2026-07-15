<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Files\PathMatcher\PathMatcher;

class NProgressAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Invoice/Asset';

    /** Related logic: https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.css */

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'invoice/css/0.2.0/nprogress.min.css',
    ];

    // Previously loaded async via cssOptions media="print" +
    // onload="this.media='all'" — that onload is an inline event handler,
    // silently blocked once CSP script-src dropped 'unsafe-inline', so the
    // stylesheet's media never switched back to 'all'. Loading it as a
    // normal blocking stylesheet instead.

    /** Related logic: https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js */

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'invoice/js/0.2.0/nprogress.min.js',
    ];
}
