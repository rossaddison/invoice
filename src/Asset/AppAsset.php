<?php

declare(strict_types=1);

namespace App\Asset;

use Yiisoft\Assets\AssetBundle;
use Yiisoft\Bootstrap5\Assets\BootstrapAsset;

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
        BootstrapAsset::class,
        Bootstrap5IconsAsset::class,
    ];
}
