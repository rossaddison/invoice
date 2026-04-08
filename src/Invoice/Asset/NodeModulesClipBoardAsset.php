<?php

declare(strict_types=1);

namespace App\Invoice\Asset;

use Yiisoft\Assets\AssetBundle;

class NodeModulesClipBoardAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@npm/clipboard/dist';

    public bool $cdn = false;

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'clipboard.min.js',
    ];
}
