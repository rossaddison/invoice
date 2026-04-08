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
}