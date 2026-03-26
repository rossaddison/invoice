<?php

declare(strict_types=1);

namespace App\Asset;

use App\Invoice\Asset\NodeModulesAsset;
use Yiisoft\Assets\AssetBundle;

final class AppAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@resources/asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
    ];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
    ];

    public array $depends = [
        // loads the package.json's node_module/bootstrap-icons
        NodeModulesAsset::class,
    ];
}
