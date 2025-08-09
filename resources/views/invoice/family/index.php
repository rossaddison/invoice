<?php

declare(strict_types=1);

use App\Invoice\Entity\Family;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
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
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var positive-int $page
 */

echo $alert;

?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')
                        ->content(' ' . Html::encode($translator->translate('family'))),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'family/index'))
    ->id('btn-reset')
    ->render();

$toolbarFilter = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('family_filters_submit')
    ->addClass('btn btn-info me-1')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href('#family_filters_submit')
    ->id('family_filters_submit')
    ->render();

$toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('families'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('family/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($translator->translate('new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
<?php
    $columns = [
        new DataColumn(
            property: 'id',
            header: $translator->translate('id'),
            content: static fn(Family $model) => Html::encode($model->getFamily_id()),
            withSorting: true,
        ),
        new DataColumn(
            property: 'family_name',
            header: $translator->translate('family'),
            content: static fn(Family $model) => Html::encode($model->getFamily_name() ?? ''),
            withSorting: true,
        ),
        new DataColumn(
            'category_primary_id',
            header: $translator->translate('category.primary'),
            content: static function (Family $model) use ($cpR, $translator): string {
                $categoryPrimaryId = $model->getCategory_primary_id();
                $categoryPrimary = $cpR->repoCategoryPrimaryQuery($categoryPrimaryId);
                return null !== $categoryPrimary ? ($categoryPrimary->getName() ?? $translator->translate('not.set'))
                                               : $translator->translate('not.set');
            },
        ),
        new DataColumn(
            'category_secondary_id',
            header: $translator->translate('category.secondary'),
            content: static function (Family $model) use ($csR, $translator): string {
                $categorySecondaryId = $model->getCategory_secondary_id();
                $categorySecondary = $csR->repoCategorySecondaryQuery($categorySecondaryId);
                return null !== $categorySecondary ? $categorySecondary->getName() ?? $translator->translate('not.set')
                                                 : $translator->translate('not.set');
            },
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (Family $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('family/view', ['id' => $model->getFamily_id()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ],
            ),
            new ActionButton(
                content: '✎',
                url: static function (Family $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('family/edit', ['id' => $model->getFamily_id()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ],
            ),
            new ActionButton(
                content: '❌',
                url: static function (Family $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('family/delete', ['id' => $model->getFamily_id()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                ],
            ),
        ]),
    ];
?>
<?php
$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$sort = Sort::only(['id'])
        ->withOrderString($sortString);
$toolbarString = Form::tag()->post($urlGenerator->generate('product/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarFilter)->encode(false)->render() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();
$sortedAndPagedPaginator = (new OffsetPaginator($families))
    ->withPageSize($s->positiveListLimit())
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));
$grid_summary = $s->grid_summary(
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
->multiSort(true)
->urlQueryParameters(['filter_product_sku', 'filter_product_price'])
->header($header)
->id('w4-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString);
?>
</div>

