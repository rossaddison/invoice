<?php

declare(strict_types=1);

namespace App\Webshop\Catalog\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * The compiled `product-zoom.ts` bundle — the hover magnifier lens on
 * `resources/views/shop/catalog/view.php`'s product photo. Not
 * registered globally: only `shop/catalog/view.php` calls
 * `$assetManager->register(self::class)`, and only when the product
 * actually has a photo to zoom into.
 *
 * Same `sourcePath`-publishes-a-`rebuild/js`-directory + esbuild-to-IIFE
 * convention as `CatalogAsset`/`App\Webshop\Cart\Asset\CartAsset` (see
 * either's own docblock for where it's copied from).
 */
final class ProductZoomAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Webshop/Catalog/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/product-zoom-iife.js',
    ];
}
