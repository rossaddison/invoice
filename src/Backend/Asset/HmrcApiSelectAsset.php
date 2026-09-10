<?php

declare(strict_types=1);

namespace App\Backend\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * The backend/hmrc "Select API to exercise" control's own JS -- CSP-safe
 * replacement for that select/button's former inline onchange/onclick
 * attributes (blocked outright by this app's `script-src 'self'`, no
 * `'unsafe-inline'`; see hmrc-api-select.ts's own docblock).
 */
class HmrcApiSelectAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Backend/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/hmrc-api-select-iife.js',
    ];
}
