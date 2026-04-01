<?php

declare(strict_types=1);

namespace App\Asset;

use Yiisoft\Assets\AssetBundle;

class ClipBoardCdnAsset extends AssetBundle
{
    public bool $cdn = true;
    
    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        '//cdn.jsdelivr.net/npm/clipboard@2.0.11/dist/clipboard.min.js',
    ];
}
