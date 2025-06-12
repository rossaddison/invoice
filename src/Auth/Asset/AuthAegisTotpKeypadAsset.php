<?php

declare(strict_types=1);

namespace App\Auth\Asset;

use Yiisoft\Assets\AssetBundle;

class AuthAegisTotpKeypadAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Auth/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/jquery-4.0.0-beta.2.min.js',
        'rebuild/js/keypad_copy_to_clipboard.js',
    ];
}
