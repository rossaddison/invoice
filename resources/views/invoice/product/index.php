<?php

declare(strict_types=1);

use App\Invoice\Entity\Product;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $products
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlFastRouteGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var bool $visible
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataProductsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataFamiliesDropdownFilter
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$allVisible = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('hide.or.unhide.columns')])
        ->addClass('btn btn-warning me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/visible', ['origin' => 'product']))
        ->id('btn-all-visible')
        ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate('product/index'))
    ->id('btn-reset')
    ->render();

// Trigger $(document).on('click', '#product_filters_submit', function () located in C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\product.js
// which in turn runs the ProductController.php index_filters function which returns the index view with the productReppositories search
$toolbarFilter = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('product_filters_submit')
    ->addClass('btn btn-info me-1')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href('#product_filters_submit')
    ->id('product_filters_submit')
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Product $model) => Html::encode($model->getProduct_id()),
        withSorting: true,
    ),
    new DataColumn(
        property: 'family_id',
        header: $translator->translate('family.name'),
        encodeHeader: true,
        content: static fn (Product $model): string => Html::encode($model->getFamily()?->getFamily_name() ?? ''),
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'name' => 'family_id',
                    'class' => 'native-reset',
                ])
                ->optionsData($optionsDataFamiliesDropdownFilter),
        visible: true,
        withSorting: true,
    ),
    new DataColumn(
        property: 'product_name',
        header: $translator->translate('product.name'),
        encodeHeader: true,
        content: static fn (Product $model): string => Html::encode($model->getProduct_name()),
        visible: true,
        withSorting: false,
    ),
    new DataColumn(
        property: 'product_sku',
        header: $translator->translate('product.sku'),
        encodeHeader: true,
        content: static fn (Product $model): string => Html::encode($model->getProduct_sku()),
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'name' => 'product_sku',
                    'class' => 'native-reset',
                ])
                ->optionsData($optionsDataProductsDropdownFilter),
        visible: true,
        withSorting: false,
    ),
    new DataColumn(
        property: 'product_description',
        header: $translator->translate('product.description'),
        content: static fn (Product $model): string => Html::encode(ucfirst($model->getProduct_description() ?? '')),
        visible: true,
        withSorting: true,
    ),
    new DataColumn(
        property: 'product_price',
        header: $translator->translate('product.price') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static fn (Product $model): string => Html::encode($model->getProduct_price()),
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'style' => 'max-width: 50px',
                    'class' => 'native-reset',
                ]),
        visible: true,
        withSorting: false,
    ),
    new DataColumn(
        property: 'product_price_base_quantity',
        header: $translator->translate('product.price.base.quantity'),
        content: static fn (Product $model): string => Html::encode($model->getProduct_price_base_quantity()),
        visible: $visible,
        withSorting: true,
    ),
    new DataColumn(
        property: 'product_unit',
        header: $translator->translate('product.unit'),
        content: static fn (Product $model): string => Html::encode((ucfirst($model->getUnit()?->getUnit_name() ?? ''))),
        visible: true,
    ),
    new DataColumn(
        property: 'tax_rate_id',
        header: $translator->translate('tax.rate'),
        content: static fn (Product $model): string => ($model->getTaxrate()?->getTaxRateId() > 0)
                    ? Html::encode($model->getTaxrate()?->getTaxRateName())
                    : $translator->translate('none'),
        visible: $visible,
        withSorting: true,
    ),
    new DataColumn(
        property: 'product_tariff',
        header: $s->getSetting('sumex') ? $translator->translate('product.tariff') . '(' . $s->getSetting('currency_symbol') . ')' : '',
        content: static fn (Product $model): string => ($s->getSetting('sumex')
                    ? Html::encode($model->getProduct_tariff())
                    : Html::encode($translator->translate('none'))),
        visible: $s->getSetting('sumex') ? true : false,
    ),
    new DataColumn(
        header: $translator->translate('product.property.add'),
        content: static function (Product $model) use ($urlGenerator): A {
            return Html::a(
                Html::tag('i', '', ['class' => 'fa fa-plus fa-margin dropdown-button text-decoration-none']),
                $urlGenerator->generate('productproperty/add', ['product_id' => $model->getProduct_id()]),
                [],
            );
        },
        encodeContent: false,
        visible: $visible,
    ),
    new ActionColumn(
        before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
        after: Html::closeTag('div'),
        buttons: [
        new ActionButton(
            content: '🔎',
            url: function (Product $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('product/view', ['id' => $model->getProduct_id()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
                'class' => 'btn btn-outline-primary btn-sm',
            ],
        ),
        new ActionButton(
            content: '✎',
            url: function (Product $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('product/edit', ['id' => $model->getProduct_id()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
                'class' => 'btn btn-outline-warning btn-sm',
            ],
        ),
        new ActionButton(
            content: '❌',
            url: function (Product $model) use ($urlGenerator): string {
                /** @psalm-suppress InvalidArgument */
                return $urlGenerator->generate('product/delete', ['id' => $model->getProduct_id()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                'class' => 'btn btn-outline-danger btn-sm',
            ],
        ),
    ]),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id', 'family_id', 'unit_id', 'tax_rate_id',
    'product_name', 'product_sku', 'product_price', 'product_description', 'product_price_base_quantity',
])
        ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($products))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('products'),
    '',
);

// Add left-aligned wrapper when additional columns are visible to accommodate more columns
$tableOrTableResponsive = $visible ? 'table-responsive' : 'table';

$toolbarString
    = Form::tag()->post($urlGenerator->generate('product/index'))->csrf($csrf)->open()
    . Div::tag()->addClass('float-start')->content(
        '<h4 class="me-3 d-inline-block">' . $translator->translate('products') . '</h4>' 
        . '<div class="btn-group me-2" role="group">'
        . $allVisible
        . $toolbarReset
        . $toolbarFilter
        . A::tag()
            ->href($urlGenerator->generate('product/add'))
            ->addClass('btn btn-info')
            ->content('➕')
            ->render()
        . '</div>'
    )->encode(false)->render()
    . Form::tag()->close();

echo $toolbarString;

if ($visible) {
    echo '<div class="text-start">';
}

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => $tableOrTableResponsive . ' table-striped text-center h-75','id' => 'table-product'])
/** @psalm-suppress InvalidArgument */
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->urlQueryParameters(['filter_product_sku', 'filter_product_price'])
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->id('w4-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlFastRouteGenerator, 'product') . ' ' . $grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'));

// Close the left-aligned wrapper div when additional columns are visible
if ($visible) {
    echo '</div>';
}
