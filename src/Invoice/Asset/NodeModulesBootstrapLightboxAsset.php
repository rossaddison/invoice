<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class NodeModulesBootstrapLightboxAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bs5-lightbox/dist';
    
    public bool $cdn = false;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'index.bundle.min.js',
    ];
}
