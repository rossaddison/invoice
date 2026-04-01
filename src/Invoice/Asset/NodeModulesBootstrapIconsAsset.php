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
}
