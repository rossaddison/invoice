<?php

declare(strict_types=1);

namespace App\Webshop\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * The compiled `storefront-tooltip.ts` bundle — activates any
 * `data-bs-toggle="tooltip"` element on `/shop` (Bootstrap never
 * auto-initializes tooltips itself). Registered directly by the
 * storefront layout (`resources/views/layout/templates/storefront/
 * main.php`), not per-view like `App\Webshop\Cart\Asset\CartAsset` —
 * the layout is the one place that's guaranteed to run for every `/shop`
 * page, and the "Change currency" tooltip it activates today lives in
 * the layout itself, not a view.
 */
final class StorefrontTooltipAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Webshop/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/storefront-tooltip-iife.js',
    ];
}
