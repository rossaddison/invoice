<?php

declare(strict_types=1);

namespace App\Asset;

use App\Invoice\Asset\NodeModulesAsset;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Bootstrap5\Assets\BootstrapCdnAsset;

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
        // necessary for the invoice\resources\views\layout\guest.php
        BootstrapCdnAsset::class,
        // loads the package.json's node_module/bootstrap-icons
        NodeModulesAsset::class,
    ];
}
