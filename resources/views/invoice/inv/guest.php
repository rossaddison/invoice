<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Inv\Inv;
use App\Invoice\Enum\DoNotSendReason;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Option;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

const NATIVE_RESET_INV_FILTER = 'native-reset inv-filter';
const NATIVE_RESET_INV_AMOUNT_FILTER = 'native-reset inv-amount-filter';

/**
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
 * @var App\Infrastructure\Persistence\UserInv\UserInv $userInv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvAmount\InvAmountRepository $iaR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\PaymentInformation\Service\BacsPaymentService $bacsPaymentService
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Infrastructure\Persistence\Worker\Worker|null $worker
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invs
 * @var bool $viewInv
 * @var bool $viewPayment
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var int $userInvListLimit
 * @var string $alert
 * @var string $csrf
 * @var string $label
 * @var string $modal_add_quote 
 * @var string $modalBacsQuickPay
 * @var string $sortString
 * @var string $status
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsStatusDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsCreditInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientsDropDownFilter
 */

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/guest'))
    ->id('btn-reset')
    ->render();

// Handled client-side by ColumnResizer.autoFit()/reset() (src/typescript/
// column-resizer.ts) — not a form submission/reload, since column widths
// are a pure client-side/localStorage concern.
$autoFitColumns = new HtmlButton()
    ->type('button')
    ->addAttributes(['data-bs-toggle' => 'tooltip',
        'title' => Html::encode($translator->translate('autofit.columns'))])
    ->addClass('btn btn-warning me-1')
    ->content('📐')
    ->id('btn-autofit-columns')
    ->render();

$resetColumnWidths = new HtmlButton()
    ->type('button')
    ->addAttributes(['data-bs-toggle' => 'tooltip',
        'title' => Html::encode($translator->translate('reset.column.widths'))])
    ->addClass('btn btn-warning me-1')
    ->content('🔄')
    ->id('btn-reset-column-widths')
    ->render();

echo new Div();

/**
 * @var ColumnInterface[] $columns
 */
// Build enabled payment gateways list once for use in the paid column
$enabledGateways = $s->paymentGatewaysEnabledDriverList();

// homecare_hidden_inv_guest_columns — see partial_settings_homecare.php.
// A no-op while HomeCare is off, same reasoning as
// InvsColumnVisibilityTrait on the staff-side inv/index grid.
$homeCareEnabled = $s->getSetting('homecare_auto_invoice_enabled') === '1';
/** @var list<string> $hiddenGuestColumns */
$hiddenGuestColumns = array_values(array_filter(explode(',',
    $s->getSetting('homecare_hidden_inv_guest_columns'))));
$isGuestColumnHidden = static fn(string $key): bool =>
    $homeCareEnabled && in_array($key, $hiddenGuestColumns, true);

$columns = [
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (Inv $model) use ($urlGenerator): A {
            return   new A()
                    ->addClass('text-decoration-none')
                    ->content(($model->getNumber() ?? '#') . ' 🔍')
                    ->href($urlGenerator->generate(
                                        'inv/view', ['id' => $model->reqId()]));
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-inv-number',
                    'class'      => NATIVE_RESET_INV_FILTER,
                    'aria-label' => 'Filter by invoice number',
                    'title'      => $translator->translate('number'),
                ])
                ->optionsData($optionsInvNumberDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterInvAmountPaid',
        header: $translator->translate('paid')
            . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use (
            $decimalPlaces, $urlGenerator, $enabledGateways, $translator
        ): string {
            $invAmountPaid  = $model->getInvAmount()->getPaid();
            $invAmountTotal = $model->getInvAmount()->getTotal();
            $paid    = $invAmountPaid  ?? 0.00;
            $total   = $invAmountTotal ?? 0.00;
            $isPaid  = $model->reqStatusId() === 4 || ($total > 0.00 && $paid >= $total);
            $paidFormatted = Html::encode(
                number_format($paid > 0.00 ? $paid : 0.00, $decimalPlaces)
            );
            $labelClass = $isPaid ? 'text-success' : 'text-danger';
            $html = '<span class="' . $labelClass . '">' . $paidFormatted . '</span>';
            $payableStatus = in_array($model->reqStatusId(), [2, 3, 5, 6], true);
            if ($payableStatus && !empty($enabledGateways)) {
                $dropdownId = 'pay-drop-' . Html::encode((string) $model->reqId());
                $items = '';
                foreach ($enabledGateways as $gateway) {
                    $displayName = str_replace('_', ' ', (string) $gateway);
                    $url = $urlGenerator->generate('paymentinformation/inform', [
                        'gateway' => $gateway,
                        'url_key' => $model->getUrlKey(),
                    ]);
                    $items .= '<li><a class="dropdown-item" href="'
                        . Html::encode($url) . '">'
                        . Html::encode($displayName) . '</a></li>';
                }
                $html .= ' <div class="dropdown d-inline-block">'
                    . '<button class="btn btn-sm btn-outline-primary dropdown-toggle"'
                    . ' type="button" id="' . $dropdownId . '"'
                    . ' data-bs-toggle="dropdown" aria-expanded="false">'
                    . '💳 ' . Html::encode($translator->translate('pay.now'))
                    . '</button>'
                    . '<ul class="dropdown-menu" aria-labelledby="'
                    . $dropdownId . '">' . $items . '</ul></div>';
            }
            return $html;
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-amount-paid',
                    'class'       => NATIVE_RESET_INV_AMOUNT_FILTER,
                    'aria-label'  => 'Filter by paid text-end',
                    'title'       => $translator->translate('paid'),
                    'placeholder' => $translator->translate('paid'),
                ]),
        withSorting: false,
        visible: $viewPayment && !$isGuestColumnHidden('paid'),
    ),
    new ActionColumn(
        header: '',
        before: Html::openTag('div', ['class' => 'dropdown'])
            . Html::openTag('button', [
                'class' => 'btn btn-info dropdown-toggle',
                'type' => 'button',
                'id' => 'dropdownMenuButton',
                'data-bs-toggle' => 'dropdown',
                'aria-haspopup' => 'true',
                'aria-expanded' => 'false',
            ])
            . Html::closeTag('button')
            . Html::openTag('div', [
                'class' => 'dropdown-menu',
                'aria-labelledby' => 'dropdownMenuButton'
            ])
            . Html::openTag('div',
                ['class' => 'btn-group', 'role' => 'group']),
        buttons: [
            new ActionButton(
                url: static function (Inv $inv) use ($urlGenerator): string {
                    return $urlGenerator->generate('inv/pdfDashboardExcludeCf',
                            ['id' => $inv->reqId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'target' => '_blank',
                    'title' => $translator->translate('download.pdf'),
                    'class' => 'bi bi-file-pdf btn btn-outline-danger btn-sm'
                    . ' dropdown-item',
                ],
            ),
            new ActionButton(
                url: static function (Inv $inv) use ($urlGenerator):
                string {
                    return $urlGenerator->generate('inv/pdfDashboardIncludeCf',
                            ['id' => $inv->reqId()]);
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'target' => '_blank',
                    'title' => $translator->translate('download.pdf')
                        . '➡️' . $translator->translate('custom.field'),
                    'class' => 'bi bi-file-pdf-fill btn btn-danger btn-sm'
                    . ' dropdown-item',
                ],
            ),
        ],
        after: Html::closeTag('div')
               . Html::closeTag('div')
               . Html::closeTag('div'),
    ),
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Inv $model) => (string) $model->reqId(),
        withSorting: true,
        visible: false,
    ),
    new DataColumn(
        property: 'filterStatus',
        header: '<span data-bs-toggle="tooltip" data-bs-html="true" title="' .
                Html::encode('🌎 ' . $translator->translate('all') . '<br/>🗋 '
                        . $translator->translate('draft')
                        . '<br/>📨 ' . $translator->translate('sent')
                        . '<br/>👀 ' . $translator->translate('viewed')
                        . '<br/>😀 ' . $translator->translate('paid')
                        . '<br/>🏦 ' . $translator->translate('overdue')
                        . '<br/>📋 ' . $translator->translate('unpaid')
                        . '<br/>📃 ' . $translator->translate('reminder')
                        . '<br/>📄 ' . $translator->translate('letter')
                        . '<br/>⚖️ ' . $translator->translate('claim')
                        . '<br/>🏛️ ' . $translator->translate('judgement')
                        . '<br/>👮 ' . $translator->translate('enforcement')
                        . '<br/>🛑️ ' . $translator->translate(
                                                    'credit.invoice.for.invoice')
                        . '<br/>❎ ' . $translator->translate('loss'))
                        . '">📊 ' . $translator->translate('status') . '</span>',
        encodeHeader: false,
        content: static function (Inv $model) use ($iR, $s, $irR, $translator):
                                                                        string {
            $statusId = $model->reqStatusId();
            $emoji = $iR->getSpecificStatusArrayEmoji($statusId);
            $label = $iR->getSpecificStatusArrayLabel((string) $statusId);
            
            // Add read-only indicator
            if (($model->getIsReadOnly())
                                && $s->getSetting('disable_read_only') == '0') {
                $label .= ' 🚫';
            }
            // Add recurring indicator
            if ($irR->repoCount($model->reqId()) > 0) {
                $label .= ' ' . $translator->translate('recurring') . ' 🔄';
            }
            
            return '<span data-bs-toggle="tooltip" title="'
            . Html::encode($label) . '" class="badge text-bg-'
            . $iR->getSpecificStatusArrayClass($statusId) . '">'
            . $emoji . ' ' . $label .  '</span>';
        },
        filter: DropdownFilter::widget()
            ->addAttributes([
                'id'         => 'filter-status',
                'name'       => 'status',
                'class'      => NATIVE_RESET_INV_FILTER,
                'aria-label' => 'Filter by status',
                'title'      => $translator->translate('status'),
            ])
            ->optionsData($optionsStatusDropDownFilter),
        encodeContent: false,
        withSorting: false,
        visible: true,
    ),
    // HomeCare field worker's do-not-send flag — only ever shown/settable
    // on the worker-scoped guest view, never for an ordinary client guest.
    new DataColumn(
        header: $translator->translate('do.not.send'),
        content: static function (Inv $model) use ($urlGenerator, $csrf, $translator): string {
            $current = $model->getDoNotSendReason();
            $options = new Option()->value('')
                ->content($translator->translate('do.not.send.not.set'))
                ->render();
            foreach (DoNotSendReason::cases() as $reason) {
                $option = new Option()->value($reason->value)
                    ->content($translator->translate('do.not.send.reason.' .
                    $reason->value));
                if ($reason->value === $current) {
                    $option = $option->selected(true);
                }
                $options .= $option->render();
            }
            return new Form()
                ->post($urlGenerator->generate('inv/setdonotsend',
                        ['inv_id' => $model->reqId()]))
                ->csrf($csrf)
                ->addAttributes(['class' => 'd-flex gap-1'])
                ->content(
                    Html::openTag('select', [
                        'name' => 'reason',
                        'class' => 'form-select form-select-sm',
                    ]) . $options . Html::closeTag('select')
                    . Html::tag('button', '💾', [
                        'type' => 'submit',
                        'class' => 'btn btn-outline-danger btn-sm',
                        'title' => $translator->translate('do.not.send'),
                    ])
                )
                ->encode(false)
                ->render();
        },
        encodeContent: false,
        visible: $worker !== null,
        withSorting: false,
    ),
    // Credit note for the invoice
    new DataColumn(
        header:  new Label()->content('💳')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('credit.invoice.for.invoice')
            ])->render(),
        encodeHeader: false,
        property: 'filterCreditInvNumber',
        content: static function (Inv $model) use ($urlGenerator, $iR): A {
            if (null!== ($cipId = $model->getCreditinvoiceParentId())) {
                $visible = $iR->repoInvUnLoadedquery($cipId);
                if (null !== $visible) {
                    $url = ($visible->getNumber() ?? '#') . '💳';
                    return   new A()
                            ->addClass('text-decoration-none')
                            ->content($url)
                            ->href($urlGenerator->generate('inv/view',
                                    ['id' => $cipId]));
                }
            }            
            return  new A()->content('')->href('');
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-credit-inv-number',
                    'class'      => NATIVE_RESET_INV_FILTER,
                    'aria-label' => 'Filter by credit note parent invoice',
                    'title'      => $translator->translate(
                        'credit.invoice.for.invoice'),
                ])
                ->optionsData($optionsCreditInvNumberDropDownFilter),
        withSorting: false,
        visible: !$isGuestColumnHidden('credit_note'),
    ),
    /**
     * Related logic: see https://github.com/rossaddison/yii-dataview/commit/9e908d87cddd0661b440cb989429e1652e00a9fe
     */
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static fn (Inv $model):
            string => Html::encode($model->getClient()?->getClientFullName()),
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-client',
                    'name'       => 'client_id',
                    'class'      => NATIVE_RESET_INV_FILTER,
                    'aria-label' => 'Filter by client',
                    'title'      => $translator->translate('client'),
                ])
                ->optionsData($optionsClientsDropDownFilter),
        withSorting: false,
        visible: !$isGuestColumnHidden('client'),
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static fn (Inv $model):
            string => (!is_string($dateCreated = $model->getDateCreated()) ?
                $dateCreated->format('Y-m-d') : ''),
        withSorting: false,
        visible: !$isGuestColumnHidden('date_created'),
    ),
    new DataColumn(
        'date_due',
        header: $translator->translate('due.date'),
        content: static function (Inv $model): Yiisoft\Html\Tag\CustomTag {
            $now = new \DateTimeImmutable('now');
            return Html::tag('label')
                    ->attributes([
                        'class' => $model->getDateDue() > $now ?
                            'badge text-bg-success' : 'badge text-bg-warning'])
                    ->content(!is_string($dateDue = $model->getDateDue()) ?
                            $dateDue->format('Y-m-d') : '');
        },
        encodeContent: false,
        withSorting: true,
        visible: !$isGuestColumnHidden('date_due'),
    ),
    new DataColumn(
        property: 'filterInvAmountTotal',
        header: $translator->translate('total')
            . ' ( ' . $s->getSetting('currency_symbol')
            . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountTotal = $model->getInvAmount()->getTotal();
            return
                 new Label()
                    ->attributes(['class' => $invAmountTotal > 0.00 ?
                            'text-success' : 'text-danger'])
                    ->content(Html::encode(null !== $invAmountTotal
                            ? number_format($invAmountTotal, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-amount-total',
                    'class'       => NATIVE_RESET_INV_AMOUNT_FILTER,
                    'aria-label'  => 'Filter by total text-end',
                    'title'       => $translator->translate('total'),
                    'placeholder' => $translator->translate('total'),
                ]),
        withSorting: false,
        visible: $viewPayment && !$isGuestColumnHidden('total'),
    ),
    new DataColumn(
        property: 'filterInvAmountBalance',
        header: $translator->translate('balance')
            . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmntBal = $model->getInvAmount()->getBalance() ?? 0;
            return new Label()
                    ->attributes(['class' => $invAmntBal > 0.00 ?
                            'text-danger' : 'text-success'])
                    ->content(Html::encode(
                            number_format($invAmntBal, $decimalPlaces)));
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-amount-balance',
                    'class'       => NATIVE_RESET_INV_AMOUNT_FILTER,
                    'aria-label'  => 'Filter by balance text-end',
                    'title'       => $translator->translate('balance'),
                    'placeholder' => $translator->translate('balance'),
                ]),
        withSorting: false,
        visible: $viewPayment && !$isGuestColumnHidden('balance'),
    ),
];

$sort = Sort::only([
        'status_id', 'number', 'date_created', 'date_due', 'id', 'client_id'])
        ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invs))
                    ->withPageSize($userInvListLimit > 0 ? $userInvListLimit : 10)
                    ->withCurrentPage($page)
                    ->withSort($sort)
                    ->withToken(PageToken::next((string) $page));


$bacsButton = $viewPayment && $bacsPaymentService->isCompanyPrivateActive()
    ? '<button type="button" class="btn btn-outline-success ms-2"'
      . ' data-bs-toggle="modal" data-bs-target="#bacsQuickPayModal">'
      . '🏦 ' . Html::encode($translator->translate('bacs.pay.by.bank.transfer'))
      . '</button>'
    : '';

$qrButton = $s->getSetting('homecare_auto_invoice_enabled') === '1'
    ? new A()
        ->addClass('btn btn-outline-secondary ms-2')
        ->addAttributes(['target' => '_blank'])
        ->content(new I()->addClass('bi bi-qr-code') . ' ' . $translator->translate('print.qr.code'))
        ->encode(false)
        ->href($urlGenerator->generate('inv/guest/qr'))
        ->render()
    : '';

// HomeCare offline PWA (see docs/HOMECARE_OFFLINE_PWA_AUGUST_2026.md) —
// worker-only, same $worker !== null gating already used for the
// do-not-send column above. An ordinary client guest never sees this.
$homeCareOfflineButtons = $worker !== null
    ? Html::openTag('button', [
            'type' => 'button',
            'id' => 'homecare-offline-download-btn',
            'class' => 'btn btn-outline-primary ms-2',
        ])
        . Html::encode($translator->translate('homecare.offline.download.button'))
        . Html::closeTag('button')
        . new A()
            ->addClass('btn btn-outline-secondary ms-2')
            ->content($translator->translate('homecare.offline.view.button'))
            ->href($urlGenerator->generate('inv/guest/offline'))
            ->render()
        . Html::tag('span', '', ['id' => 'homecare-offline-status', 'class' => 'ms-2'])
    : '';

$toolbarString =  new Form()->post(
                $urlGenerator->generate('inv/guest'))->csrf($csrf)->open()
        .  new Div()->addClass('float-start m-3')->content(
                 new H4()
                    ->addClass('me-3 d-inline-block')
                    ->content($translator->translate('invoice')
                )
            .   $toolbarReset
            .   $autoFitColumns
            .   $resetColumnWidths
            .   Button::ascDesc(
                $urlGenerator, 'client_id', 'warning',
                $translator->translate('client'), true)
            .   $bacsButton
            .   $qrButton
            .   $homeCareOfflineButtons
                )->encode(false)->render()
        .  new Form()->close();

$gridSummary = $s->gridSummary(
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
    ->tableAttributes([
        'class' => 'table table-striped text-center h-75 resizable-grid',
        'id' => 'table-invoice-guest'])
    ->columns(...$columns)
    ->columnGrouping(true)
    ->dataReader($sortedAndPagedPaginator)
    ->urlCreator($urlCreator)
    // the up and down symbol will appear at first indicating that the column
    // can be sorted. It also appears in this state if another column has been
    // sorted
    ->sortableHeaderPrepend(
                '<div class="float-end text-secondary text-opacity-50">⭥</div>')
    // the up arrow will appear if column values are ascending
    ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
    // the down arrow will appear if column values are descending
    ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->emptyCell($translator->translate('not.set'))
    ->emptyCellAttributes(['style' => 'color:red'])
    ->id('w9-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget(
                                                    $sortedAndPagedPaginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($gridSummary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
<div id="angular-amount-magnifier-app">
    <app-root></app-root>
</div>

<?php
/**
 * InvoiceAmountMagnifier + group-toggle logic moved to the shared
 * src/typescript/list-utils.ts + inv-index.ts (bundled into
 * invoice-typescript-iife.js) — the same code the authenticated
 * inv/index.php page already uses, reached here via
 * InvoiceApp.initInvIndex('table-invoice-guest', ...) self-invoking from
 * index.ts when #table-invoice-guest is present. This also eliminates what
 * was a near-duplicate implementation. script-src no longer needs
 * 'unsafe-inline' for this.
 */

$invStyle = <<<CSS
.amount-magnifiable {
    transition: all 0.25s ease-in-out;
    display: inline-block;
}

.amount-magnifiable:hover {
    cursor: pointer;
}

/* Ensure magnified elements appear above other content */
.amount-magnifiable[style*="z-index: 1000"] {
    z-index: 1000 !important;
    position: relative !important;
}

/* Group Header Styles */
.group-header {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%) !important;
    border-top: 3px solid #495057 !important;
    border-bottom: 1px solid #495057 !important;
}

.group-header td {
    position: sticky;
    top: 0;
    z-index: 10;
}

.group-header:hover {
    background: linear-gradient(135deg, #5a6268 0%, #495057 100%) !important;
}

/* Grouping controls */
.btn-group .form-select {
    border-left: 0;
    border-radius: 0 0.375rem 0.375rem 0;
}

.btn-group label.btn {
    border-right: 0;
    border-radius: 0.375rem 0 0 0.375rem;
}

/* Collapsible group rows */
.group-collapsible {
    cursor: pointer;
    user-select: none;
}

.group-collapsed + tr {
    display: none;
}

/* Group toggle icon animation */
.group-toggle-icon {
    transition: transform 0.3s ease;
}

.group-toggle-icon.bi-chevron-right {
    transform: rotate(0deg);
}

.group-toggle-icon.bi-chevron-down {
    transform: rotate(0deg);
}

/* Add subtle indentation for grouped rows */
.group-header + tr td:first-child {
    border-left: 4px solid #007bff;
}

/* Make invoice rows within groups slightly indented visually */
tbody tr:not(.group-header) {
    background-color: rgba(0, 0, 0, 0.02);
}

tbody tr:not(.group-header):hover {
    background-color: rgba(0, 123, 255, 0.1);
}

/* Sticky group headers when scrolling */
@media (min-width: 768px) {
    .group-header td {
        position: sticky;
        top: 60px; /* Adjust based on your header height */
        z-index: 20;
    }
}

/* Status column tooltip font size - 2x larger */
.label[data-bs-toggle="tooltip"] + .tooltip .tooltip-inner,
.tooltip.show .tooltip-inner {
    font-size: 2em !important;
}

/* ── Filter row: shared styles ── */
.inv-filter {
    font-size: 1rem;
    font-weight: 500;
    max-width: 160px;
    border-left: 4px solid transparent;
    border-radius: 4px;
    padding: 4px 8px;
    width: 100%;
    box-sizing: border-box;
}

/* Colour-coded left border per filter */
#filter-inv-number        { border-left-color: #0d6efd; } /* blue   – invoice #    */
#filter-credit-inv-number { border-left-color: #6610f2; } /* indigo – credit note  */
#filter-status            { border-left-color: #198754; } /* green  – status       */
#filter-client     { border-left-color: #0dcaf0; } /* cyan  – client    */

/* Amount text filters */
.inv-amount-filter {
    font-size: 1rem;
    font-weight: 500;
    text-align: right;
    border-left: 4px solid transparent;
    border-radius: 4px;
    padding: 4px 8px;
    width: 100%;
    box-sizing: border-box;
}

#filter-amount-total   { border-left-color: #20c997; } /* teal   – total   */
#filter-amount-paid    { border-left-color: #198754; } /* green  – paid    */
#filter-amount-balance { border-left-color: #ffc107; } /* amber  – balance */

@media (max-width: 767.98px) {
    .inv-filter, .inv-amount-filter {
        max-width: 100%;
        font-size: 1.1rem;
        padding: 8px 10px;
        display: block;
        margin-bottom: 4px;
    }
}
CSS;

echo Html::style($invStyle);

$filterPromptLabels = json_encode([
    'filter-inv-number'        => '— ' . $translator->translate('number') . ' —',
    'filter-credit-inv-number' => '— ' . $translator->translate(
        'credit.invoice.for.invoice') . ' —',
    'filter-status'            => '— ' . $translator->translate('status') . ' —',
    'filter-client'     => '— ' . $translator->translate('client') . ' —',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
// Consumed by InvoiceApp.initInvIndex('table-invoice-guest', 'inv-guest-filter-config')
// self-invoking from index.ts — a JSON data-island rather than an
// executable inline <script>, matching inv/index.php's #inv-filter-config
// pattern, so script-src no longer needs 'unsafe-inline' for this.
echo Html::tag('script', $filterPromptLabels, ['type' => 'application/json', 'id' => 'inv-guest-filter-config']);

// Consumed by initHomeCareOffline() (src/typescript/homecare-offline.ts) —
// same JSON-data-island reasoning as inv-guest-filter-config above. Only
// worker-scoped guests ever have the download button this drives, but the
// island itself is harmless to emit unconditionally.
$homeCareOfflineLabels = json_encode([
    'success' => $translator->translate('homecare.offline.download.success'),
    'failed' => $translator->translate('homecare.offline.download.failed'),
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);
echo Html::tag('script', $homeCareOfflineLabels, ['type' => 'application/json', 'id' => 'homecare-offline-i18n']);

// MobilePreviewToggle already instantiated by
// InvoiceApp.initInvIndex('table-invoice-guest', ...) above — was a
// near-duplicate of the same class in src/typescript/inv-index.ts.

if ($viewPayment && $bacsPaymentService->isCompanyPrivateActive()) {
   echo $modalBacsQuickPay;
}
