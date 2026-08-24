<?php

declare(strict_types=1);

use App\Webshop\Catalog\Asset\ProductZoomAsset;
use App\Webshop\Catalog\ProductListing;
use App\Webshop\Currency\CurrencyContext;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var AssetManager $assetManager
 * @var CurrencyContext $currency
 * @var ProductListing $product
 */

$this->setTitle($product->displayName());

if ($product->imageUrl !== null) {
    $assetManager->register(ProductZoomAsset::class);
    $this->addJsFiles($assetManager->getJsFiles());
}
?>
<?php if ($product->imageUrl !== null): ?>
<?= Html::style(
    // The lens: a square outline that tracks the pointer, positioned by
    // product-zoom.ts via inline left/top (px, relative to .js-zoom-wrapper).
    '.zoom-lens { position: absolute; display: none; width: 140px; height: 140px; pointer-events: none;'
    . ' border: 2px solid var(--bs-primary, #0d6efd); background: rgba(13, 110, 253, .15); }'
    // The pane: sits to the right of the photo at the same size, filled
    // by product-zoom.ts with a magnified background-image crop.
    . '.zoom-pane { position: absolute; display: none; top: 0; left: 100%; margin-left: 1rem;'
    . ' width: 100%; height: 100%; background-repeat: no-repeat; pointer-events: none;'
    . ' border: 1px solid var(--bs-border-color, #dee2e6); border-radius: var(--bs-border-radius, .375rem);'
    . ' background-color: var(--bs-body-bg, #fff); box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .2); z-index: 20; }'
    // Below the md breakpoint the image column is full-width and stacked
    // above the details, so there's no gutter for the pane to sit in —
    // matches the Bootstrap col-md-5/7 split just below.
    . '@media (max-width: 767.98px) { .zoom-pane { display: none !important; } }',
) ?>
<?php endif; ?>
<p><?= Html::a('← Back to products', $urlGenerator->generate('shop/catalog/index')) ?></p>
<div class="row g-4">
    <div class="col-md-5">
        <?php if ($product->imageUrl !== null): ?>
        <div class="position-relative js-zoom-wrapper" style="aspect-ratio: 1 / 1;">
            <?= Html::img($product->imageUrl)
                ->alt($product->displayName())
                ->addClass('img-fluid rounded js-zoom-image')
                ->addAttributes(['style' => 'aspect-ratio: 1 / 1; object-fit: cover; width: 100%;']) ?>
            <div class="zoom-lens js-zoom-lens"></div>
            <div class="zoom-pane js-zoom-pane"></div>
        </div>
        <?php else: ?>
        <div
            class="d-flex align-items-center justify-content-center text-muted bg-body-secondary rounded"
            style="aspect-ratio: 1 / 1;"
        >No photo</div>
        <?php endif; ?>
    </div>
    <div class="col-md-7">
        <h1><?= Html::encode($product->displayName()) ?></h1>
        <?php if ($product->sku !== null && $product->sku !== ''): ?>
        <p class="text-muted">SKU: <?= Html::encode($product->sku) ?></p>
        <?php endif; ?>
        <?php if ($product->description !== null && $product->description !== ''): ?>
        <p><?= Html::encode($product->description) ?></p>
        <?php endif; ?>
        <p class="fs-4 fw-bold">
            <?= Html::encode($currency->format($product->price)) ?>
            <?php if ($product->unit !== null && $product->unit !== ''): ?>
                <span class="fs-6 text-muted">/ <?= Html::encode($product->unit) ?></span>
            <?php endif; ?>
        </p>
        <?= new Form()
            ->post($urlGenerator->generate('shop/cart/add'))
            ->csrf($csrf)
            ->open() ?>
        <?= Html::hiddenInput('product_id', (string) $product->id) ?>
        <div class="input-group mb-3" style="max-width: 12rem;">
            <?= Html::input('number', 'quantity', '1', ['min' => '1', 'step' => '1', 'class' => 'form-control']) ?>
        </div>
        <button type="submit" class="btn btn-primary">Add to cart</button>
        <?= new Form()->close() ?>
    </div>
</div>
