<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class Bootstrap5LightBoxCdnAsset extends AssetBundle
{
    public bool $cdn = true;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        '//cdn.jsdelivr.net/npm/bs5-lightbox@1.8.5/dist/index.bundle.min.js',
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js',
    ];
}
