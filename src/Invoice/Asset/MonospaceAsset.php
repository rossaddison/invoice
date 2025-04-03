<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class MonospaceAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Invoice/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [
        'invoice/css/monospace.css',
    ];
}
