<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class NodeModulesAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/bootstrap-icons/font';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'bootstrap-icons.min.css',
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
    ];
}
