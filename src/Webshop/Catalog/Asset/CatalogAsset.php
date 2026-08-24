<?php

declare(strict_types=1);

namespace App\Webshop\Catalog\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * The compiled `price-range.ts` bundle — the dual-handle price slider
 * layered over `resources/views/shop/catalog/index.php`'s min_price/
 * max_price `<input type="number">` fields. Not registered globally:
 * only `shop/catalog/index.php` calls
 * `$assetManager->register(self::class)`, and only when there's a real
 * price range to build a slider over (see that view).
 *
 * Same `sourcePath`-publishes-a-`rebuild/js`-directory + esbuild-to-IIFE
 * convention as `App\Webshop\Cart\Asset\CartAsset` (see that class's own
 * docblock for where it's copied from).
 */
final class CatalogAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Webshop/Catalog/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/price-range-iife.js',
    ];
}
