<?php

declare(strict_types=1);

use App\Webshop\Catalog\ProductListing;
use App\Webshop\Currency\CurrencyContext;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Router\UrlGeneratorInterface;

/**
 * The same kind of "pick a product" carousel `shop/catalog/index.php`
 * shows (same `Yiisoft\Bootstrap5\Carousel` widget, same tile shape) —
 * a separate, compact instance purpose-built for this slot, not that
 * page's own copy reused as-is: no filter sidebar (no room for one here,
 * and this is a "here's everything, grab one more thing" prompt, not a
 * browsing session), smaller tiles, no heading matching that page's
 * search-results title. Sits above the cart table and the checkout order
 * summary (`App\Webshop\Cart\CartController`/`App\Webshop\Checkout\
 * CheckoutController`, both via `App\Webshop\Controller\
 * StorefrontController::productGallery()`) so a customer who's already at
 * cart or checkout and wants one more thing never has to leave to "go
 * back to products" — the gallery to pick from is already right there,
 * so neither of those two pages carries that link.
 *
 * @var UrlGeneratorInterface $urlGenerator
 * @var CurrencyContext $currency
 * @var list<ProductListing> $galleryProducts
 * @var string|null $galleryReturnTo Route-generated path (not a route
 *     name) a tile's "add to cart" should land back on — e.g. checkout,
 *     so picking one more thing from there doesn't strand the customer on
 *     the cart page instead. Threaded through as a `redirect` query
 *     param on the product-detail link, then a hidden field on that
 *     page's own add-to-cart form (see shop/catalog/view.php),
 *     validated same-origin only when `App\Webshop\Cart\CartController::
 *     add()` actually redirects. Omit (null) when this partial is
 *     rendered on the cart page itself — CartController::add()'s own
 *     default (shop/cart/index) is already the right landing spot.
 */

if ($galleryProducts === []) {
    return;
}

$productsPerSlide = 3;
// Smaller than the full catalog page's own carousel (220px) — this is a
// secondary "pick one more thing" prompt above the real content of the
// page, not the page's main event.
$tileImageHeight = 140;

$placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true" class="text-white-50">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';

$renderTile = function (ProductListing $product) use (
    $urlGenerator,
    $placeholderSvg,
    $tileImageHeight,
    $currency,
    $galleryReturnTo,
): string {
    $media = $product->imageUrl !== null
        ? new Img()
            ->src($product->imageUrl)
            ->alt($product->displayName())
            ->addAttributes(['style' => 'max-height: ' . $tileImageHeight . 'px; object-fit: contain;'])
            ->render()
        : $placeholderSvg;

    $caption = '<div class="text-white mt-2 small">'
        . Html::encode($product->displayName())
        . ' — ' . Html::encode($currency->format($product->price))
        . '</div>';

    $href = $urlGenerator->generate(
        'shop/catalog/show',
        ['id' => (string) $product->id],
        $galleryReturnTo !== null ? ['redirect' => $galleryReturnTo] : [],
    );

    return new A()
        ->href($href)
        ->addClass('d-flex flex-column align-items-center text-decoration-none')
        ->content(
            '<div class="d-flex align-items-center justify-content-center" style="height: '
                . $tileImageHeight . 'px;">' . $media . '</div>' . $caption,
        )
        ->encode(false)
        ->render();
};

$items = [];
foreach (array_chunk($galleryProducts, $productsPerSlide) as $slideIndex => $slideProducts) {
    $tiles = array_map($renderTile, $slideProducts);

    $content = '<div class="bg-dark d-flex align-items-start justify-content-around gap-3 p-3">'
        . implode('', $tiles) . '</div>';

    $items[] = CarouselItem::to(
        content: $content,
        active: $slideIndex === 0,
    );
}
?>
<section class="mb-4">
    <h2 class="h6 text-muted mb-2">Add something else</h2>
    <?= Carousel::widget()->items(...$items)->render() ?>
</section>
