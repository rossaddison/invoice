<?php
declare(strict_types=1);

use App\Invoice\Entity\InvAllowanceCharge;
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
use Yiisoft\Router\CurrentRoute;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invAllowanceCharges
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $sortString
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 */
$vat = $s->getSetting('enable_vat_registration');

echo $alert;
?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('allowance.or.charge.inv')),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'invallowancecharge/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<div>
<br>    
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('id'),
            content: static fn(InvAllowanceCharge $model) => $model->getId(),
            withSorting: true,
        ),
        new DataColumn(
            property: 'filterInvNumber',
            header: $translator->translate('invoice'),
            content: static function (InvAllowanceCharge $model) use ($urlGenerator): A {
                return Html::a($model->getInv()?->getNumber() ?? '#', $urlGenerator->generate('inv/view', ['id' => $model->getInv_id()]), []);
            },
            filter: $optionsDataInvNumberDropDownFilter,
            withSorting: false,
        ),
        new DataColumn(
            property: 'filterReasonCode',
            header: $translator->translate('allowance.or.charge.reason.code'),
            content: static function (InvAllowanceCharge $model): string {
                return $model->getAllowanceCharge()?->getReasonCode() ?? '';
            },
            filter: true,
            withSorting: true,
        ),
        new DataColumn(
            property: 'filterReason',
            header: $translator->translate('allowance.or.charge.reason'),
            content: static function (InvAllowanceCharge $model): string {
                return $model->getAllowanceCharge()?->getReason() ?? '';
            },
            filter: true,
            withSorting: true,
        ),
        new DataColumn(
            property: 'amount',
            header: $translator->translate('allowance.or.charge.amount'),
            content: static fn(InvAllowanceCharge $model) => $model->getAmount(),
            withSorting: true,
        ),
        new DataColumn(
            property: 'vat_or_tax',
            header: $vat ? $translator->translate('vat') : $translator->translate('tax'),
            content: static fn(InvAllowanceCharge $model) => $model->getVatOrTax(),
            withSorting: true,
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharge/view', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                ],
            ),
            new ActionButton(
                content: '✎',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharge/edit', ['id' => $model->getId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('edit'),
                ],
            ),
            new ActionButton(
                content: '❌',
                url: static function (InvAllowanceCharge $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('invallowancecharge/delete', ['id' => $model->getId()]);
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

$toolbarString = Form::tag()->post($urlGenerator->generate('invallowancecharge/index'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    Form::tag()->close();

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$sort = Sort::only(['id', 'inv_id', 'allowance_charge_id', 'amount', 'vat_or_tax'])
    ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invAllowanceCharges))
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ? $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('allowance.or.charge.inv'),
    '',
);

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-allowancecharge'])
->columns(...$columns)
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->header($header)
->id('w937-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'invallowancecharge') . ' ' . $grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
?>
