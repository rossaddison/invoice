<?php

declare(strict_types=1);

use App\Webshop\Catalog\Asset\CatalogAsset;
use App\Webshop\Catalog\ProductFilter;
use App\Webshop\Catalog\ProductListing;
use App\Webshop\Currency\CurrencyContext;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Bootstrap5\Carousel;
use Yiisoft\Bootstrap5\CarouselItem;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Img;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var AssetManager $assetManager
 * @var CurrencyContext $currency
 * @var list<ProductListing> $products
 * @var ProductFilter $filter
 * @var list<string> $categoryOptions
 * @var list<string> $subcategoryOptions
 * @var list<string> $familyOptions
 * @var float|null $catalogMinPrice
 * @var float|null $catalogMaxPrice
 */

// $this->setTitle() must be the first statement after the docblock —
// same narrow Psalm parser quirk noted in the storefront layout's own
// $this->beginPage() comment.
$this->setTitle('Products');

// Same Bootstrap5\Carousel widget as this app's own resources/views/site/gallery.php
// — a rotating showcase to browse the range, not the transaction surface
// itself; each slide links through to the product's own detail page,
// where "Add to cart" already lives (a quantity input + form doesn't fit
// naturally inside a carousel slide). Three products per slide, chunked
// in catalog order.
$productsPerSlide = 3;
$tileImageHeight = 220;

// Muted placeholder for products with no photo yet — same shopping-bag
// glyph as the navbar logo, so a mix of real photos and placeholders
// still reads as one consistent design rather than a broken-image icon.
$placeholderSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"'
    . ' fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"'
    . ' stroke-linejoin="round" aria-hidden="true" class="text-white-50">'
    . '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/>'
    . '<path d="M3 6h18"/>'
    . '<path d="M16 10a4 4 0 0 1-8 0"/>'
    . '</svg>';

/** Renders one product's image/placeholder + name + price, linked through
 * to its own detail page — the unit repeated three-up per slide. */
$renderTile = function (ProductListing $product) use ($urlGenerator, $placeholderSvg, $tileImageHeight, $currency): string {
    $media = $product->imageUrl !== null
        ? new Img()
            ->src($product->imageUrl)
            ->alt($product->displayName())
            ->addAttributes(['style' => 'max-height: ' . $tileImageHeight . 'px; object-fit: contain;'])
            ->render()
        : $placeholderSvg;

    $caption = '<div class="text-white mt-2">'
        . Html::encode($product->displayName())
        . ' — ' . Html::encode($currency->format($product->price))
        . '</div>';

    return new A()
        ->href($urlGenerator->generate('shop/catalog/show', ['id' => (string) $product->id]))
        ->addClass('d-flex flex-column align-items-center text-decoration-none')
        ->content(
            '<div class="d-flex align-items-center justify-content-center" style="height: '
                . $tileImageHeight . 'px;">' . $media . '</div>' . $caption,
        )
        ->encode(false)
        ->render();
};

/**
 * One checkbox-group `<fieldset>` for the filter sidebar — Category,
 * Subcategory or Family. Options come from the *full* unfiltered catalog
 * (see ProductsController::distinctValues()'s own docblock), so checking
 * one group's box never makes another group's option disappear. Renders
 * nothing for a group with no options at all (e.g. a storefront with no
 * categorized products yet).
 *
 * @param list<string> $options
 * @param list<string> $selected
 */
$renderFilterGroup = function (string $legend, string $paramName, array $options, array $selected): string {
    if ($options === []) {
        return '';
    }

    $checkboxes = '';
    /** @var list<string> $options */
    foreach ($options as $option) {
        $id = $paramName . '-' . md5($option);
        $checkboxes .= '<div class="form-check">'
            . Html::checkbox($paramName . '[]', $option, ['id' => $id, 'class' => 'form-check-input'])
                ->checked(in_array($option, $selected, true))
                ->render()
            . '<label class="form-check-label" for="' . Html::encode($id) . '">'
                . Html::encode($option) . '</label>'
            . '</div>';
    }

    return '<fieldset class="mb-3"><legend class="h6">' . Html::encode($legend) . '</legend>'
        . $checkboxes . '</fieldset>';
};

// A real drag-slider over the plain min/max inputs — only worth
// registering when there's an actual range to drag across (see
// CatalogAsset's own docblock, and price-range.ts's matching
// DOMContentLoaded guard for the no-JS/degenerate-range case).
$hasPriceRange = $catalogMinPrice !== null && $catalogMaxPrice !== null && $catalogMinPrice < $catalogMaxPrice;
if ($hasPriceRange) {
    $assetManager->register(CatalogAsset::class);
    $this->addJsFiles($assetManager->getJsFiles());
}
?>
<?php if ($hasPriceRange): ?>
<?= Html::style(
    '.price-slider { position: relative; height: 1.5rem; margin: 0.75rem 0 0.25rem; }'
    . '.price-slider-track { position: absolute; top: 50%; left: 0; right: 0; height: 4px;'
    . ' background: var(--bs-secondary-bg, #e9ecef); border-radius: 2px; transform: translateY(-50%); }'
    . '.price-slider-range { position: absolute; top: 50%; height: 4px;'
    . ' background: var(--bs-primary, #0d6efd); border-radius: 2px; transform: translateY(-50%); }'
    . '.price-slider-thumb { position: absolute; top: 0; left: 0; width: 100%; height: 1.5rem; margin: 0;'
    . ' background: transparent; pointer-events: none; appearance: none; -webkit-appearance: none; }'
    . '.price-slider-thumb::-webkit-slider-runnable-track { background: transparent; }'
    . '.price-slider-thumb::-moz-range-track { background: transparent; }'
    . '.price-slider-thumb::-webkit-slider-thumb { pointer-events: auto; appearance: none;'
    . ' -webkit-appearance: none; width: 1rem; height: 1rem; margin-top: 0; border-radius: 50%;'
    . ' background: var(--bs-primary, #0d6efd); border: 2px solid var(--bs-body-bg, #fff);'
    . ' box-shadow: 0 0 0 1px rgba(0, 0, 0, .15); cursor: pointer; }'
    . '.price-slider-thumb::-moz-range-thumb { pointer-events: auto; width: 1rem; height: 1rem; border: 2px solid var(--bs-body-bg, #fff);'
    . ' border-radius: 50%; background: var(--bs-primary, #0d6efd); box-shadow: 0 0 0 1px rgba(0, 0, 0, .15); cursor: pointer; }'
    // The magnifier bubble — hidden until price-range.ts shows it
    // mid-drag (see PriceRangeSlider.showBubble()), positioned by its
    // own inline `left` (a percentage along the track, set from JS) and
    // centered on that point via the transform below.
    . '.price-slider-bubble { position: absolute; bottom: 100%; margin-bottom: 0.6rem; transform: translateX(-50%);'
    . ' display: none; background: var(--bs-dark, #212529); color: var(--bs-light, #fff); font-size: 1rem;'
    . ' font-weight: 700; padding: 0.25rem 0.6rem; border-radius: var(--bs-border-radius, .375rem);'
    . ' white-space: nowrap; pointer-events: none; z-index: 5; }'
    . '.price-slider-bubble::after { content: ""; position: absolute; top: 100%; left: 50%;'
    . ' transform: translateX(-50%); border: 5px solid transparent; border-top-color: var(--bs-dark, #212529); }',
) ?>
<?php endif; ?>
<div class="row">
    <aside class="col-md-3 mb-4">
        <h2 class="h6 mb-3">Filters</h2>
        <?= Html::openTag('form', ['method' => 'get', 'action' => $urlGenerator->generate('shop/catalog/index')]) ?>
        <?php // Carries the navbar search box's term forward — applying a
        // sidebar filter is meant to narrow the current search results,
        // not replace them with an unfiltered, unsearched catalog. ?>
        <?= Html::hiddenInput('q', $filter->search) ?>
        <fieldset class="mb-3">
            <?php $currencyInfoForFilter = $currency->info(); ?>
            <legend class="h6">
                Price
                <?php if ($currency->isShowingDocumentCurrency() && $currencyInfoForFilter !== null): ?>
                <?php // Filter bounds always compare against the catalog's real
                // native-currency price (App\Webshop\Catalog\ProductFilter::apply()) —
                // converting them too would mean typing a number in one
                // currency that silently filters in another, so this label
                // says which currency they're actually in instead. ?>
                <span class="text-muted fw-normal">(<?= Html::encode($currencyInfoForFilter->native) ?>)</span>
                <?php endif; ?>
            </legend>
            <?php if ($hasPriceRange): ?>
            <div
                id="price-filter"
                class="d-none"
                data-catalog-min="<?= (string) $catalogMinPrice ?>"
                data-catalog-max="<?= (string) $catalogMaxPrice ?>"
            >
                <div class="price-slider">
                    <div class="price-slider-track"></div>
                    <div class="price-slider-range js-price-slider-range"></div>
                    <div class="price-slider-bubble js-price-slider-bubble"></div>
                    <input type="range" class="price-slider-thumb js-price-slider-min" aria-label="Minimum price" style="z-index: 2;">
                    <input type="range" class="price-slider-thumb js-price-slider-max" aria-label="Maximum price" style="z-index: 1;">
                </div>
                <div class="d-flex justify-content-between small text-muted mb-2">
                    <span class="js-price-slider-min-label"></span>
                    <span class="js-price-slider-max-label"></span>
                </div>
            </div>
            <?php endif; ?>
            <div class="d-flex align-items-center gap-2">
                <?= Html::input(
                    'number',
                    'min_price',
                    $filter->minPrice !== null ? (string) $filter->minPrice : null,
                    [
                        'class' => 'form-control form-control-sm js-price-min-input',
                        'placeholder' => $catalogMinPrice !== null ? number_format($catalogMinPrice, 2) : 'Min',
                        'min' => '0',
                        'step' => '0.01',
                        'aria-label' => 'Minimum price',
                    ],
                ) ?>
                <span class="text-muted">–</span>
                <?= Html::input(
                    'number',
                    'max_price',
                    $filter->maxPrice !== null ? (string) $filter->maxPrice : null,
                    [
                        'class' => 'form-control form-control-sm js-price-max-input',
                        'placeholder' => $catalogMaxPrice !== null ? number_format($catalogMaxPrice, 2) : 'Max',
                        'min' => '0',
                        'step' => '0.01',
                        'aria-label' => 'Maximum price',
                    ],
                ) ?>
            </div>
        </fieldset>
        <?= $renderFilterGroup('Category', 'category', $categoryOptions, $filter->categories) ?>
        <?= $renderFilterGroup('Subcategory', 'subcategory', $subcategoryOptions, $filter->subcategories) ?>
        <?= $renderFilterGroup('Family', 'family', $familyOptions, $filter->families) ?>
        <button type="submit" class="btn btn-primary btn-sm w-100 mb-2">Apply filters</button>
        <?php if (!$filter->isEmpty()): ?>
        <?= Html::a('Clear filters', $urlGenerator->generate('shop/catalog/index'), ['class' => 'btn btn-link btn-sm w-100']) ?>
        <?php endif; ?>
        <?= Html::closeTag('form') ?>
    </aside>
    <section class="col-md-9">
        <h1 class="mb-4">
            <?= $filter->search !== '' ? 'Results for "' . Html::encode($filter->search) . '"' : 'Products' ?>
        </h1>
        <?php if ($products === []): ?>
            <p class="text-muted">
                <?= $filter->search !== ''
                    ? 'No products match "' . Html::encode($filter->search) . '".'
                    : 'No products match those filters.' ?>
            </p>
        <?php else: ?>
        <?php
        $items = [];
        foreach (array_chunk($products, $productsPerSlide) as $slideIndex => $slideProducts) {
            $tiles = array_map($renderTile, $slideProducts);

            $content = '<div class="bg-dark d-flex align-items-start justify-content-around gap-3 p-4">'
                . implode('', $tiles) . '</div>';

            $items[] = CarouselItem::to(
                content: $content,
                active: $slideIndex === 0,
            );
        }
        echo Carousel::widget()->items(...$items)->render();
        ?>
        <?php endif; ?>
    </section>
</div>
