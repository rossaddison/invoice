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

    /** Related logic: https://cdnjs.cloudflare.com/ajax/libs/nprogress/0.2.0/nprogress.min.js */

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'invoice/js/0.2.0/nprogress.min.js',
    ];
}
