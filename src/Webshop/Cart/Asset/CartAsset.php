<?php

declare(strict_types=1);

namespace App\Webshop\Cart\Asset;

use Yiisoft\Assets\AssetBundle;

/**
 * The compiled `cart.ts` bundle — progressive enhancement for
 * `resources/views/shop/cart/index.php`'s quantity-update/remove forms
 * (see CartController::wantsJson()). Not registered globally: only
 * `shop/cart/index.php` calls `$assetManager->register(self::class)`,
 * since nothing else on the site needs it.
 *
 * Same `sourcePath` (published from a `rebuild/js` directory next to the
 * bundle class) + esbuild-to-IIFE convention as this app's own
 * `App\Auth\Asset\AuthAegisTotpKeypadAsset`.
 */
final class CartAsset extends AssetBundle
{
    public ?string $basePath = '@assets';

    public ?string $baseUrl = '@assetsUrl';

    public ?string $sourcePath = '@src/Webshop/Cart/Asset';

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $css = [];

    /** @psalm-suppress NonInvariantDocblockPropertyType */
    public array $js = [
        'rebuild/js/cart-iife.js',
    ];
}
