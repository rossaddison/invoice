<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invs
 * @var bool $viewInv
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var int $userInvListLimit
 * @var string $alert
 * @var string $csrf
 * @var string $label
 * @var string $modal_add_quote
 * @var string $sortString
 * @var string $status
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 */

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

$statusBar =  Div::tag()
        ->addClass('submenu-row')
        ->content(
            Div::tag()
                ->addClass('btn-group index-options')
                ->content(
                    Html::a(
                        $iR->getSpecificStatusArrayEmoji(0) . ' ' . $translator->translate('all'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 0]),
                        [
                            'class' => 'btn btn-' . ($status == 0 ? $iR->getSpecificStatusArrayClass(0) : 'btn-default'),
                        ],
                    )
                    // Guests are never sent draft invoices i.e. status = '1'
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(2) . ' ' . $translator->translate('sent'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 2]),
                        [
                            'class' => 'btn btn-' . ($status == 2 ? $iR->getSpecificStatusArrayClass(2) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(3) . ' ' . $translator->translate('viewed'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 3]),
                        [
                            'class' => 'btn btn-' . ($status == 3 ? $iR->getSpecificStatusArrayClass(3) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(4) . ' ' . $translator->translate('paid'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 4]),
                        [
                            'class' => 'btn btn-' . ($status == 4 ? $iR->getSpecificStatusArrayClass(4) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(5) . ' ' . $translator->translate('overdue'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 5]),
                        [
                            'class' => 'btn btn-' . ($status == 5 ? $iR->getSpecificStatusArrayClass(5) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(6) . ' ' . $translator->translate('unpaid'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 6]),
                        [
                            'class' => 'btn btn-' . ($status == 6 ? $iR->getSpecificStatusArrayClass(6) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(7) . ' ' . $translator->translate('reminder'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 7]),
                        [
                            'class' => 'btn btn-' . ($status == 7 ? $iR->getSpecificStatusArrayClass(7) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(8) . ' ' . $translator->translate('letter'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 8]),
                        [
                            'class' => 'btn btn-' . ($status == 8 ? $iR->getSpecificStatusArrayClass(8) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(9) . ' ' . $translator->translate('claim'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 9]),
                        [
                            'class' => 'btn btn-' . ($status == 9 ? $iR->getSpecificStatusArrayClass(9) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(10) . ' ' . $translator->translate('judgement'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 10]),
                        [
                            'class' => 'btn btn-' . ($status == 10 ? $iR->getSpecificStatusArrayClass(10) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(11) . ' ' . $translator->translate('enforcement'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 11]),
                        [
                            'class' => 'btn btn-' . ($status == 11 ? $iR->getSpecificStatusArrayClass(11) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(12) . ' ' . $translator->translate('credit.invoice.for.invoice'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 12]),
                        [
                            'class' => 'btn btn-' . ($status == 12 ? $iR->getSpecificStatusArrayClass(12) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    )
                    . Html::a(
                        $iR->getSpecificStatusArrayEmoji(13) . ' ' . $translator->translate('loss'),
                        $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 13]),
                        [
                            'class' => 'btn btn-' . ($status == 13 ? $iR->getSpecificStatusArrayClass(13) : 'btn-default'),
                            'style' => 'text-decoration:none',
                        ],
                    ),
                )
                ->encode(false)
                ->render(),
        )
        ->encode(false)
        ->render();

/**
 * @var ColumnInterface[] $columns
 */
$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn(Inv $model) => (string) $model->getId(),
        withSorting: true,
    ),
    new ActionColumn(
        buttons: [
            new ActionButton(
                url: static function (Inv $model) use ($translator, $urlGenerator): string {
                    return $urlGenerator->generate('inv/pdf', ['include' => 0]);
                },
                attributes: [
                    'style' => 'text-decoration:none',
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('download.pdf'),
                    'class' => 'bi bi-file-pdf',
                ],
            ),
            new ActionButton(
                url: static function (Inv $model) use ($translator, $urlGenerator): string {
                    return $urlGenerator->generate('inv/pdf', ['include' => 1]);
                },
                attributes: [
                    'style' => 'text-decoration:none',
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('download.pdf') . '➡️' . $translator->translate('custom.field'),
                    'class' => 'bi bi-file-pdf-fill',
                ],
            ),
        ],
    ),
    new DataColumn(
        'status_id',
        header: $translator->translate('status'),
        content: static function (Inv $model) use ($s, $iR, $irR, $translator): Yiisoft\Html\Tag\CustomTag {
            $label = $iR->getSpecificStatusArrayLabel((string) $model->getStatus_id());
            if (($model->getIs_read_only()) && $s->getSetting('disable_read_only') === (string) 0) {
                $label .=  ' 🚫';
            }
            if ($irR->repoCount((string) $model->getId()) > 0) {
                $label .= $translator->translate('recurring') . ' 🔄';
            }
            return Html::tag('span', $iR->getSpecificStatusArrayEmoji((int) $model->getStatus_id()) . $label, ['class' => 'label label-' . $iR->getSpecificStatusArrayClass((int) $model->getStatus_id())]);
        },
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (Inv $model) use ($urlGenerator): A {
            return  A::tag()
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->content(($model->getNumber() ?? '#') . ' 🔍')
                    ->href($urlGenerator->generate('inv/view', ['id' => $model->getId()]));
        },
        encodeContent: false,
        filter: $optionsDataInvNumberDropDownFilter,
        withSorting: false,
    ),
    new DataColumn(
        header: '💳',
        property: 'creditinvoice_parent_id',
        content: static function (Inv $model) use ($urlGenerator, $iR): A {
            $visible = $iR->repoInvUnLoadedquery($model->getCreditinvoice_parent_id());
            $url = ($model->getNumber() ?? '#') . '💳';
            return  A::tag()
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->content($visible ? $url : '')
                    ->href($urlGenerator->generate('inv/view', ['id' => $model->getCreditinvoice_parent_id()]));
        },
        encodeContent: false,
        withSorting: false,
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client'),
        content: static fn(Inv $model): string => $model->getClient()?->getClient_name() ?? '',
        withSorting: false,
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static fn(Inv $model): string => (!is_string($dateCreated = $model->getDate_created()) ? $dateCreated->format('Y-m-d') : ''),
        withSorting: false,
    ),
    new DataColumn(
        'date_due',
        header: $translator->translate('due.date'),
        content: static function (Inv $model) use ($dateHelper): Yiisoft\Html\Tag\CustomTag {
            $now = new \DateTimeImmutable('now');
            return Html::tag('label')
                    ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                    ->content(!is_string($dateDue = $model->getDate_due()) ? $dateDue->format('Y-m-d') : '');
        },
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'filterInvAmountTotal',
        header: $translator->translate('total') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountTotal = $model->getInvAmount()->getTotal();
            return
                Label::tag()
                    ->attributes(['class' => $invAmountTotal > 0.00 ? 'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $invAmountTotal
                            ? number_format($invAmountTotal, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px']),
        withSorting: false,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('paid') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountPaid = $model->getInvAmount()->getPaid();
            return Label::tag()
                    ->attributes(['class' => $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal() ? 'label label-danger' : 'label label-success'])
                    ->content(Html::encode(null !== $invAmountPaid
                            ? number_format($invAmountPaid > 0.00 ? $invAmountPaid : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        withSorting: false,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('balance') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountBalance = $model->getInvAmount()->getBalance();
            return  Label::tag()
                    ->attributes(['class' => $invAmountBalance > 0.00 ? 'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $invAmountBalance
                            ? number_format($invAmountBalance > 0.00 ? $invAmountBalance : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        withSorting: false,
    ),
];

$sort = Sort::only(['status_id', 'number', 'date_created', 'date_due', 'id', 'client_id'])
        ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invs))
                    ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
                    ->withCurrentPage($page)
                    ->withSort($sort)
                    ->withToken(PageToken::next((string) $page));


$toolbarString = Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open()
        . $statusBar
        . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
        . Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('client'), true))->encode(false)->render()
        . Form::tag()->close();

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    !empty($userInvListLimit) ? $userInvListLimit : 10,
    $translator->translate('invoices'),
    $label,
);

$urlCreator = new UrlCreator($urlGenerator);
$order =  OrderHelper::stringToArray($sortString);
$urlCreator->__invoke([], $order);

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-invoice-guest'])
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
    ->header($translator->translate('invoice'))
    ->emptyCell($translator->translate('not.set'))
    ->emptyCellAttributes(['style' => 'color:red'])
    ->id('w9-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($sortedAndPagedPaginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate(($viewInv
                       ? $pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'inv', $defaultPageSizeOffsetPaginator) : '') . ' '
                       . $grid_summary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
