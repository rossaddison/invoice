<?php

declare(strict_types=1);

use App\Invoice\Entity\Family;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;;
use Yiisoft\Html\Tag\Input;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\CategoryPrimary\CategoryPrimaryRepository $cpR
 * @var App\Invoice\CategorySecondary\CategorySecondaryRepository $csR
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $families
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $modal_generate_products
 * @var string $sortString
 * @psalm-var positive-int $page
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

/**
 * Used with the checkbox column to generate products from selected families
 * Related logic: see family.js handleGenerateProducts function
 */
$generateProductsButton = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-success')
    ->href('#')
    ->content('☑️' . $translator->translate('generate')
        . ' '
        . $translator->translate('products')
        . '🏭')
    ->id('btn-generate-products')
    ->render();

$toolbarReset = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'family/index'))
    ->id('btn-reset')
    ->render();

$toolbarFilter = new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('family_filters_submit')
    ->addClass('btn btn-info me-1')
    ->content(new I()->addClass('bi bi-bootstrap-reboot'))
    ->href('#family_filters_submit')
    ->id('family_filters_submit')
    ->render();

$columns = [
    new CheckboxColumn(
        /**
         * Related logic: see header checkbox: name: 'checkbox-selection-all'
         */
        content: static function (Checkbox $input, DataContext $context): string {
            $family = $context->data;
            if (($family instanceof Family)
                    && (null !== ($id = $family->getFamilyId()))) {
                return  new Input()
                       ->type('checkbox')
                       ->addAttributes([
                           'id' => $id,
                           'name' => 'family_ids[]',
                           'data-bs-toggle' => 'tooltip'
                        ])
                       ->value($id)
                       ->disabled(null!== $family->getFamilyCommalist()
                               && null !== $family->getFamilyProductprefix()
                               ? false : true)
                       ->render();
            }
            return '';
        },
        multiple: false,
    ),
    new DataColumn(
        property: 'id',
        header: $translator->translate('id'),
        content: static fn (Family $model) => Html::encode($model->getFamilyId()),
        withSorting: true,
    ),
    new DataColumn(
        property: 'family_name',
        header: $translator->translate('family'),
        content: static fn (Family $model) => '<span data-family-name>'
            . Html::encode($model->getFamilyName() ?? '') . '</span>',
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'family_commalist',
        header: $translator->translate('family.comma.list'),
        content: static fn (Family $model) => '<span data-family-commalist>'
            . Html::encode($model->getFamilyCommalist() ?? '') . '</span>',
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'family_productprefix',
        header: $translator->translate('family.product.prefix'),
        content: static fn (Family $model) => '<span data-family-prefix>'
            . Html::encode($model->getFamilyProductprefix() ?? '') . '</span>',
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        'category_primary_id',
        header: $translator->translate('category.primary'),
        content: static function (Family $model) use ($cpR, $translator): string {
            $categoryPrimaryId = $model->getCategoryPrimaryId();
            $categoryPrimary = $cpR->repoCategoryPrimaryQuery($categoryPrimaryId);
            return null !== $categoryPrimary ?
                    ($categoryPrimary->getName() ?? $translator->translate('not.set'))
                        : $translator->translate('not.set');
        },
    ),
    new DataColumn(
        'category_secondary_id',
        header: $translator->translate('category.secondary'),
        content: static function (Family $model) use ($csR, $translator): string {
            $categorySecondaryId = $model->getCategorySecondaryId();
            $categorySecondary = $csR->repoCategorySecondaryQuery($categorySecondaryId);
            return null !== $categorySecondary ?
                    $categorySecondary->getName() ?? $translator->translate('not.set')
                                             : $translator->translate('not.set');
        },
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Family $model) use ($urlGenerator): string {
                return $urlGenerator->generate('family/view',
                    ['id' => $model->getFamilyId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (Family $model) use ($urlGenerator): string {
                return $urlGenerator->generate('family/edit',
                    ['id' => $model->getFamilyId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
            ],
        ),
        new ActionButton(
            content: '❌',
            url: static function (Family $model) use ($urlGenerator): string {
                return $urlGenerator->generate('family/delete',
                    ['id' => $model->getFamilyId()]);
            },
            attributes: [
                'title' => $translator->translate('delete'),
                'onclick' => "return confirm("
                    . "'"
                    . $translator->translate('delete.record.warning')
                    . "');",
            ],
        ),
    ]),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id'])
        ->withOrderString($sortString);

$toolbarString =  new Form()->post($urlGenerator->generate('family/index'))->csrf($csrf)->open()
    .  new A()
        ->href($urlGenerator->generate('family/add'))
        ->addClass('btn btn-info')
        ->content('➕')
        ->render()
    // use the checkboxcolumn to generate products from selected families
    .  new Div()
        ->addClass('float-end m-3')
        ->content($generateProductsButton)
        ->encode(false)
        ->render()
    .  new Div()
        ->addClass('float-end m-3')
        ->content($toolbarFilter)
        ->encode(false)
        ->render()
    .  new Div()
        ->addClass('float-end m-3')
        ->content($toolbarReset)
        ->encode(false)
        ->render()
    .  new Form()->close();

$sortedAndPagedPaginator = (new OffsetPaginator($families))
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('families'),
    '',
);

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75', 'id' => 'table-product'])
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('families'))
->multiSort(true)
->id('w4-grid')
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate('<div class="d-flex align-items-center">'
        . $pageSizeLimiter::buttons(
            $currentRoute, $s, $translator, $urlGenerator, 'family')
        . ' ' . $gridSummary . '</div>')
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

echo $modal_generate_products;
