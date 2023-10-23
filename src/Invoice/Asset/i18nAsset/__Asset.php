<?php

declare(strict_types=1);

namespace App\Invoice\Asset\i18nAsset;

use Yiisoft\Assets\AssetBundle;

class __Asset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Invoice/Asset';

    public array $css = [];
}
