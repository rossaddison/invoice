<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Html\Tag\Span;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\GridView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\GridView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;
use Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter;

const BTN_SUCCESS = 'btn btn-success';
const BTN_INFO = 'btn btn-info';
const INV_ROUTE_INDEX = 'inv/index';
const INV_ROUTE_EDIT = 'inv/edit';
const INV_AMOUNT_FILTER_CLASS = 'native-reset inv-amount-filter';
const INV_AMOUNT_FILTER_STYLE = 'text-align: right; display: block; width: 100%;';
const LABEL_SUCCESS = 'label label-success';
const LABEL_WARNING = 'label label-warning';

/**
 * Related logic: see config/common/params.php
 *  'yiisoft/view => ['gridComponents' => Reference::to(GridComponents::class)]',
 */

/**
 * @var App\Invoice\DeliveryLocation\DeliveryLocationRepository $dlR
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\InvSentLog\InvSentLogRepository $islR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $invs
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var bool $visible
 * @var bool $visibleToggleInvSentLogColumn
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $groupBy
 * @var string $label
 * @var string $modal_add_inv
 * @var string $modal_copy_inv_multiple
 * @var string $modal_create_recurring_multiple
 * @var string $nativeResetInvFilter
 * @var string $sortString
 * @var string $status
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsCreditInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsFamilyNameDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientsDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsYearMonthDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsStatusDropDownFilter
 */
$nativeResetInvFilter = 'native-reset inv-filter';
$settingTabIndex = 'setting/tabIndex';

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo Breadcrumbs::widget()
 ->links(
     BreadcrumbLink::to(
         label: $translator->translate('default.invoice.group'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_group]',
         ),
         active: true,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultInvoiceGroup ?? $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.terms'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[default_invoice_terms]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('default_invoice_terms')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('default.payment.method'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoice_default_payment_method]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $defaultInvoicePaymentMethod
             ?? $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('invoices.due.after'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[invoices_due_after]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('invoices_due_after')
             ?: $translator->translate('not.set'),
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('generate.invoice.number.for.draft'),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[generate_invoice_number_for_draft]',
         ),
         active: false,
         attributes: [
             'data-bs-toggle' => 'tooltip',
             'title' => $s->getSetting('generate_invoice_number_for_draft')
                == '1' ? '✅' : '❌',
         ],
         encodeLabel: false,
     ),
     BreadcrumbLink::to(
         label: $translator->translate('recurring'),
         url: $urlGenerator->generate('invrecurring/index'),
     ),
     BreadcrumbLink::to(
         label: $translator->translate('set.to.read.only')
             . ' '
             . $iR->getSpecificStatusArrayEmoji(
                (int) $s->getSetting('read_only_toggle')),
         url: $urlGenerator->generate(
             $settingTabIndex,
             [],
             ['active' => 'invoices'],
             'settings[read_only_toggle]',
         ),
     ),
 )
 ->listId(false)
 ->render();

/**
 * Use with the checkbox column to copy invoices according to date.
 */

$copyInvoiceMultiple =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'modal',
            'title' => Html::encode($translator->translate('copy.invoice'))])
        ->addClass(BTN_SUCCESS)
        /**
         * Purpose: Trigger modal_copy_inv_multiple.php to pop up
         * Related logic: see id="modal-copy-inv-multiple" class="modal" on resources/views/invoice/inv/modal_copy_inv_multiple.php
         */
        ->href('#modal-copy-inv-multiple')
        ->content('☑️' . $translator->translate('copy.invoice'))
        ->id('btn-modal-copy-inv-multipe')
        ->render();

/**
 * Use with the checkbox column to mark invoices as sent.
 * Note an email is not sent. The invoices appear on the client's guest index
 * NB: Only invoices marked as sent can appear on the client's side.
 *  i.e no 'draft' invoices can appear on the client guest index
 * Related logic:
 *  see \invoice\src\Invoice\Asset\rebuild\js\inv.js
 *  $(document).on('click', '#btn-mark-as-sent', function () {
 */
$markAsSent =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'tooltip',
            'title' => Html::encode($translator->translate('sent'))])
        ->addClass(BTN_SUCCESS)
        ->content('☑️' . $translator->translate('sent')
                . $iR->getSpecificStatusArrayEmoji(2))
        // src/typescript/invoice.ts
        ->id('btn-mark-as-sent')
        ->render();

/**
 * Use with the checkbox column to mark invoices as draft. The customer will
 * no longer be able to view the invoice on their side.
 * Related logic: see src/typescript/invoice.ts
 */
$disabledMarkSentAsDraft =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'tooltip',
            'title' => Html::encode(
                    $translator->translate('security.disable.read.only.info'))])
        ->addAttributes([
            'disabled' => 'disabled',
            'style' => 'text-decoration:none',
        ])
        ->addClass(BTN_SUCCESS)
        ->content('☑️' . $translator->translate('draft')
                . $iR->getSpecificStatusArrayEmoji(1))
        ->id('btn-mark-sent-as-draft')
        ->render();

$enabledMarkSentAsDraft =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'tooltip',
            'title' => Html::encode($translator->translate('draft'))])
        ->addAttributes([
            'style' => 'text-decoration:none',
        ])
        ->addClass(BTN_SUCCESS)
        ->content('☑️' . $translator->translate('draft')
                . $iR->getSpecificStatusArrayEmoji(1))
        ->id('btn-mark-sent-as-draft')
        ->render();

/**
 * Used with the checkbox column to use resources/views/invoice/inv/modal_create_recurring_multiple.php
 * Related logic: see https://emojipedia.org/recycling-symbol
 */
$markAsRecurringMultiple =  new A()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'modal'])
        ->addClass(BTN_INFO)
        /**
         * Purpose: Trigger modal_create_recurring_modal.php to pop up
         * Related logic: see id="create-recurring-multiple" class="modal" on resources/views/invoice/inv/modal_create_recurring_multiple.php
         */
        ->href('#create-recurring-multiple')
        ->content('☑️' . $translator->translate('recurring') . '♻️')
        ->render();

$toolbarReset =  new A()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-primary me-1 ajax-loader')
        ->content( new I()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? INV_ROUTE_INDEX))
        ->id('btn-reset')
        ->render();

$allVisible =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('hide.or.unhide.columns')])
        ->addClass('btn btn-warning me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/visible', ['origin' => 'inv']))
        ->id('btn-all-visible')
        ->render();

$toggleColumnInvSentLog =  new A()
        ->addAttributes([
            'type' => 'reset',
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('hide.or.unhide.columns')])
        ->addClass(BTN_INFO . ' me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/toggleinvsentlogcolumn'))
        ->id('btn-all-visible');

$enabledAddInvoiceButton =  new A()
        ->addAttributes([
            'class' => BTN_INFO,
            'data-bs-toggle' => 'modal',
            'style' => 'text-decoration:none',
        ])
        ->content('➕')
        ->href('#modal-add-inv')
        ->id('btn-enabled-invoice-add-button')
        ->render();

$disabledAddInvoiceButton =  new A()
        ->addAttributes([
            'class' => BTN_INFO,
            'data-bs-toggle' => 'tooltip',
            'title' => $translator->translate('add.client'),
            'disabled' => 'disabled',
            'style' => 'text-decoration:none',
        ])
        ->content('➕')
        ->href('#modal-add-inv')
        ->id('btn-disabled-invoice-add-button')
        ->render();

$enableGrouping = $groupBy !== 'none';

$sort = Sort::only([
    'id',
    'status_id',
    'number',
    'date_created',
    'date_due',
    'client_id'
])
// (Related logic: see vendor\yiisoft\data\src\Reader\Sort
// - => 'desc'  so -id => default descending on id
->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($invs))
    ->withPageSize(
        $defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1
    )
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

// Calculate totals for footer (from paginator to avoid exhausting $invs)
$totalAmount = 0.0;
$totalPaid = 0.0;
$totalBalance = 0.0;

/**
 * @var Inv $invoice
 */
foreach ($sortedAndPagedPaginator->read() as $invoice) {
    $totalAmount += null !== ($total = $invoice->getInvAmount()->getTotal())
            ? $total : 0.00;
    $totalPaid += null !== ($paid = $invoice->getInvAmount()->getPaid())
            ? $paid : 0.00;
    $totalBalance += null !== ($balance = $invoice->getInvAmount()->getBalance())
            ? $balance : 0.00;
}

/**
 * @var ColumnInterface[] $columns
 */
$columns = [
    new CheckboxColumn(
        /**
         * Related logic: see header checkbox: name: 'checkbox-selection-all'
         */
        content: static function (Checkbox $input, DataContext $context)
            use ($translator): string {
            $inv = $context->data;
            if (($inv instanceof Inv) && (null !== ($id = $inv->getId()))) {
                return  $input
                       ->addAttributes([
                           'id' => $id,
                           'name' => 'checkbox[]',
                           'data-bs-toggle' => 'tooltip',
                           'title' => $inv->getInvAmount()->getTotal() == 0
                               ? $translator->translate(
                               'index.checkbox.add.some.items.to.enable') : ''])
                       ->value($id)
                       ->disabled($inv->getInvAmount()->getTotal() > 0 ?
                               false : true)
                       ->render();
            }
            return '';
        },
        multiple: true,
    ),
    new ActionColumn(
        header: '',
        before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
        after: Html::closeTag('div'),
        buttons: [
            new ActionButton(
// is_read_only false, disable_read_only 0, status draft1 => ✎, not disabled
// is_read_only false, disable_read_only 1, status draft1 => ❗✎, not disabled
// is_read_only true, disable_read_only 0, status  sent2 => 🚫, disabled
// is_read_only true, disable_read_only 1, status sent2 => ❗, not disabled
                content: static function (Inv $inv) use ($s): string {
                    $iRO = $inv->getIsReadOnly();
                    $dRO = $s->getSetting('disable_read_only');
                    $status = $inv->getStatusId();
                    $iconMap = [
                        /** editable draft */
                        'false' => [
                            /** protection is on */
                            '0' => [
                                /** draft can be editable */
                                '1' => '✎',
                            ],
                            /** protection is off */
                            '1' => [
                            /** warning: editing a draft with protection off */
                                '1' => '❗✎',
                            ],
                        ],
                        /** non editable invoice */
                        'true' => [
                            /** protection is on */
                            '0' => [
                            /** an invoice marked as sent cannot be edited */
                                '2' => '🚫',
                            ],
                            /** protection is off */
                            '1' => [
          /** warning: you are editing an invoice whilst protection is off */
                                '2' => '❗',
                            ],
                        ],
                    ];
                    $iROString = $iRO ? 'true' : 'false';
                    /**
                     * @var array $iconMap[$iROString]
                     * @var array $iconMap[$iROString][$dRO]
                     * @var string $iconMap[$iROString][$dRO][$status]
                     */
                    $icon = $iconMap[$iROString][$dRO][$status] ?? '';
                    return !empty($icon) ? $icon : '🚫';
                },
                url: static function (Inv $inv) use ($s, $urlGenerator): string {
                    $iRO = $inv->getIsReadOnly();
                    $dRO = $s->getSetting('disable_read_only');
                    $status = $inv->getStatusId();
                    $urlMap = [
                        /** editable draft **/
                        'false' => [
                            /** protection is on */
                            '0' => [
                                '1' => $urlGenerator->generate(
                                    INV_ROUTE_EDIT,
                                    ['id' => $inv->getId()],
                                ),
                            ],
                            /** protection is off */
                            '1' => [
                    /** Allow editing of draft, even though protection is off */
                                '1' => $urlGenerator->generate(
                                    INV_ROUTE_EDIT,
                                    ['id' => $inv->getId()],
                                ),
                            ],
                        ],
                        /** not editable invoice */
                        'true' => [
                            /** protection is on */
                            '0' => [
                         /** Invoice cannot be edited whilst protection is on */
                                '2' => '',
                            ],
                            /** protection is off */
                            '1' => [
                    /** Allow the editing of invoice whilst protection is off */
                                '2' => $urlGenerator->generate(
                                    INV_ROUTE_EDIT,
                                    ['id' => $inv->getId()],
                                ),
                            ],
                        ],
                    ];

                    $iROString = $iRO ? 'true' : 'false';
                    /**
                     * @var array $urlMap[$iROString]
                     * @var array $urlMap[$iROString][$dRO]
                     * @var string $urlMap[$iROString][$dRO][$status]
                     */
                    return $urlMap[$iROString][$dRO][$status] ?? '';
                },
                attributes: static function (Inv $inv) use ($s, $translator): array {
                    $iRO = $inv->getIsReadOnly();
                    $dRO = $s->getSetting('disable_read_only');
                    $status = $inv->getStatusId();
                    $attributesMap = [
                        /** editable draft **/
                        'false' => [
                            /** protection is on */
                            '0' => [
                                /** draft invoices can be edited */
                                '1' => [
                                    'data-bs-toggle' => 'tooltip',
                                    'title' => $translator->translate('edit'),
                                    'class' => 'btn btn-outline-warning btn-sm'],
                            ],
                            /** protection is off */
                            '1' => [
                                '1' => [
                                    'data-bs-toggle' => 'tooltip',
                                    'title' => $translator->translate(
                        'security.disable.read.only.true.draft.check.and.mark'),
                                    'class' => 'btn btn-warning btn-sm',
                                ],
                            ],
                        ],
                        /** not editable invoice */
                        'true' => [
                            /** protection is on */
                            '0' => [
                         /** Invoice cannot be edited whilst protection is on */
                                '2' => [
                                    'data-bs-toggle' => 'tooltip',
                                    'title' => $translator->translate('sent'),
                                    'disabled' => 'disabled',
                                    'aria-disabled' => 'true',
                                    'class' => 'btn btn-secondary btn-sm disabled',
                                    'style' => 'pointer-events:none'],
                            ],
                            /** protection is off */
                            '1' => [
                    /** Allow the editing of invoice whilst protection is off */
                                '2' => [
                                    'data-bs-toggle' => 'tooltip',
                                    'title' => $translator->translate(
                         'security.disable.read.only.true.sent.check.and.mark'),
                                    'class' => 'btn btn-outline-danger btn-sm'],
                            ],
                        ],
                    ];
                    $iROString = $iRO ? 'true' : 'false';
                    /**
                     * @var array $attributesMap[$iROString]
                     * @var array $attributesMap[$iROString][$dRO]
                     * @var array $attributesMap[$iROString][$dRO][$status]
                     */
                    return $attributesMap[$iROString][$dRO][$status] ?? [];
                },
            ),
        ],
    ),
    new ActionColumn(
        header: '',
        before: Html::openTag('div', ['class' => 'dropdown'])
            . Html::openTag('button', [
                'class' => BTN_INFO . ' dropdown-toggle',
                'type' => 'button',
                'id' => 'dropdownMenuButton',
                'data-toggle' => 'dropdown',
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
                url: static function (Inv $inv)
                                    use ($urlGenerator): string {
                    return $urlGenerator->generate('inv/pdfDashboardExcludeCf',
                            ['id' => $inv->getId()]);
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
                            ['id' => $inv->getId()]);
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
            new ActionButton(
                content: '📨',
                url: static function (Inv $inv) use ($urlGenerator): string {
                    // draft invoices cannot be emailed
                    if ($inv->getStatusId() !== 1) {
                        return $urlGenerator->generate('inv/emailStage0',
                                                        ['id' => $inv->getId()]);
                    }
                    return '';
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('email.warning.draft'),
                    'class' => 'btn btn-outline-primary btn-lg dropdown-item',
                ],
            ),
        ],
        after: Html::closeTag('div')
               . Html::closeTag('div')
               . Html::closeTag('div'),
    ),
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (Inv $model) use ($urlGenerator): A {
            return   new A()
                    ->addAttributes([
                        'class' => 'btn btn-primary btn-lg',
                        'style' => 'text-decoration:none'])
                    ->content(($model->getNumber() ?? '#')
                            . ' 🔍'
                            // List the first item on the invoice as a reminder
                            . $model->getFirstItemFamilyProductName())
                    ->href($urlGenerator->generate('inv/view',
                                                    ['id' => $model->getId()]));
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-inv-number',
                    'name'       => 'number',
                    'class'      => $nativeResetInvFilter,
                    'aria-label' => 'Filter by invoice number',
                    'title'      => $translator->translate('number'),
                ])
                ->optionsData($optionsInvNumberDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterFamilyName',
        header: $translator->translate('family.name'),
        content: static function (Inv $model): string {
            // List the family of the first item on the invoice
            // as a reminder e.g. On a window cleaning run the
            // first product on the invoice (only one anyway)
            // is the 'product' house number of the original
            // csv_comma_list.
            // Family is the run's name. So the total
            // index can be sorted according to the run name
            return $model->getFirstItemFamilyName();
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-family-name',
                    'name'       => 'number',
                    'class'      => $nativeResetInvFilter,
                    'aria-label' => 'Filter by family name',
                    'title'      => $translator->translate('family.name'),
                ])
                ->optionsData($optionsFamilyNameDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterDateCreatedYearMonth',
        header: $translator->translate(
            'datetime.immutable.date.created.mySql.format.year.month.filter'),
        content: static fn (Inv $model):
                        string => ($model->getDateCreated())->format('Y-m-d'),
        filter: DropdownFilter::widget()
            ->addAttributes([
                'id'         => 'filter-year-month',
                'name'       => 'number',
                'class'      => $nativeResetInvFilter,
                'aria-label' => 'Filter by year-month',
                'title'      => $translator->translate(
                    'datetime.immutable.date.created.mySql.format.year.month.filter'),
            ])
            ->optionsData($optionsYearMonthDropDownFilter),
        withSorting: false,
        visible: $visible,
    ),
    new DataColumn(
        property: 'filterStatus',
        header: '<span data-bs-toggle="tooltip" data-bs-html="false" title="' .
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
            $statusId = $model->getStatusId();
            if ($statusId === null) {
                return '<span class="badge text-bg-default">N/A</span>';
            }
            $emoji = $iR->getSpecificStatusArrayEmoji($statusId);
            $label = $iR->getSpecificStatusArrayLabel((string) $statusId);
            
            // Add read-only indicator
            if (($model->getIsReadOnly())
                                && $s->getSetting('disable_read_only') == '0') {
                $label .= ' 🚫';
            }
            // Add recurring indicator
            if ($irR->repoCount((string) $model->getId()) > 0) {
                $label .= ' ' . $translator->translate('recurring') . ' 🔄';
            }
            
            return '<span data-bs-toggle="tooltip" title="'
            . Html::encode($label) . '" class="badge text-bg-'
            . $iR->getSpecificStatusArrayClass($statusId) . '">'
            . $emoji . Html::encode($label) . '</span>';
        },
        filter: DropdownFilter::widget()
            ->addAttributes([
                'id'         => 'filter-status',
                'name'       => 'status',
                'class'      => $nativeResetInvFilter,
                'aria-label' => 'Filter by status',
                'title'      => $translator->translate('status'),
            ])
            ->optionsData($optionsStatusDropDownFilter),
        encodeContent: false,
        withSorting: false,
        visible: $visible,
    ),
    // Make a client active / inactive via client/edit
    new DataColumn(
        header:  new Label()->content('🔛️')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('active')
            ])->render(),
        encodeHeader: false,
        property: 'id',
        content: static function (Inv $model) use ($urlGenerator): A {
            return  new A()
                ->addAttributes([
                    'style' => 'text-decoration:none',
                ])
                ->href($urlGenerator->generate('client/edit',
                    ['id' => $model->getClient()?->getClientId(),
                        'origin' => 'inv']))
                ->content($model->getClient()?->getClientActive() ? '✅' : '❌'
            );
        },
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
            $visible = $iR->repoInvUnLoadedquery(
                                        $model->getCreditinvoiceParentId());
            if (null !== $visible) {
                $url = ($visible->getNumber() ?? '#') . '💳';
                return   new A()
                        ->addAttributes(['style' => 'text-decoration:none'])
                        ->content($url)
                        ->href($urlGenerator->generate('inv/view',
                                ['id' => $model->getCreditinvoiceParentId()]));
            }
            return  new A()->content('')->href('');
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-credit-inv-number',
                    'class'      => $nativeResetInvFilter,
                    'aria-label' => 'Filter by credit note parent invoice',
                    'title'      => $translator->translate(
                        'credit.invoice.for.invoice'),
                ])
                ->optionsData($optionsCreditInvNumberDropDownFilter),
        withSorting: false,
        visible: $visible,
    ),
    // If more than one email has been sent regarding this invoice,
    // present a toggle button to display the table to the right of
    // the toggle button
    new DataColumn(
        'invsentlogs',
        header:  new Label()->content('↔️')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => 'toggle',
            ])->render(),
        encodeHeader: false,
        content: static function (Inv $model)
    use ($islR, $toggleColumnInvSentLog ): string|A {
            $modelId = $model->getId();
            if (null !== $modelId) {
             $count = $islR->repoInvSentLogEmailedCountForEachInvoice($modelId);
                if ($count > 0) {
                    return $toggleColumnInvSentLog;
                } else {
                    return '0 📧';
                }
            }
            return '';
        },
        encodeContent: false,
    ),
    // Link to invsentlog index where the index has been filtered according to inv number
    new DataColumn(
        'invsentlogs',
        header:  new Label()->content('➡️📧')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('email.logs.with.filter')
            ])->render(),
        encodeHeader: false,
        content: static function (Inv $model) use
        ($islR, $urlGenerator, $translator): string|A {
            $modelId = $model->getId();
            if (null !== $modelId) {
                $count = $islR->repoInvSentLogEmailedCountForEachInvoice($modelId);
                if ($count > 0) {
                    return (new A())
                    ->addAttributes([
                        'type' => 'reset',
                        'data-bs-toggle' => 'tooltip',
                        'title' => $translator->translate('email.logs')])
                    ->addClass(BTN_SUCCESS . ' me-1')
                    ->content((string) $count)
                    ->href($urlGenerator->generate(
                            'invsentlog/index',
                            [],
                            ['filterInvNumber' => $model->getNumber()]))
                    ->id('btn-all-visible');
                }
            }
            return '0 📧';
        },
        encodeContent: false,
        visible: $visible,
    ),
    // A table of emails specific to the invoice
    new DataColumn(
        header:  new Label()->content('|||')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('email.logs.table')
            ])->render(),
        encodeHeader: false,
        content: static function (Inv $model)
                            use ($islR, $urlGenerator, $gridComponents): string {
            $modelId = $model->getId();
            if (null !== $modelId) {
                $invSentLogs = $islR->repoInvSentLogForEachInvoice($modelId);
                /**
                 * Related logic: see Initialize an ArrayCollection
                 */
                $model->setInvSentLogs();
                /**
                 * @var App\Invoice\Entity\InvSentLog $invSentLog
                 */
                foreach ($invSentLogs as $invSentLog) {
                    $model->addInvSentLog($invSentLog);
                }
                return $gridComponents->gridMiniTableOfInvSentLogsForInv(
                    $model,
                    4,
                    $urlGenerator,
                );
            }
            return '0 📧';
        },
        visible: $visibleToggleInvSentLogColumn,
        encodeContent: false,
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
                    'class'      => $nativeResetInvFilter,
                    'aria-label' => 'Filter by client',
                    'title'      => $translator->translate('client'),
                ])
                ->optionsData($optionsClientsDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        'client_number',
        header: $translator->translate('client.number'),
        content: static fn (Inv $model):
                string => Html::encode($model->getClient()?->getClientNumber()),
        encodeContent: false,
    ),
    new DataColumn(
        property: 'filterClientAddress1',
        header: $translator->translate('street.address'),
        content: static fn (Inv $model):
            string => Html::encode($model->getClient()?->getClientAddress1()),
        encodeContent: false,
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-address-1',
                    'class'       => $nativeResetInvFilter,
                    'aria-label'  => 'Filter by street address',
                    'title'       => $translator->translate('street.address'),
                    'placeholder' => $translator->translate('street.address'),
                ]),
    ),
    new DataColumn(
        'client_address_2',
        header: $translator->translate('street.address.2'),
        content: static fn (Inv $model):
            string => Html::encode($model->getClient()?->getClientAddress2()),
        encodeContent: false,
    ),
        new DataColumn(
        property: 'filterClientGroup',
        header: $translator->translate('client.group'),
        content: static fn (Inv $model):
                        string => $model->getClient()?->getClientGroup() ?? '',
        filter: DropdownFilter::widget()
            ->addAttributes([
                'id'         => 'filter-client-group',
                'name'       => 'number',
                'class'      => $nativeResetInvFilter,
                'aria-label' => 'Filter by client group',
                'title'      => $translator->translate('client.group'),
            ])
            ->optionsData($optionsClientGroupDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        'time_created',
        header: $translator->translate('datetime.immutable.time.created'),
        // Show only the time of the DateTimeImmutable
        content: static fn (Inv $model):
                        string => ($model->getTimeCreated())->format('H:i:s'),
        visible: $visible,
    ),
    new DataColumn(
        'date_modified',
        header: $translator->translate('datetime.immutable.date.modified'),
        content: static function (Inv $model): Label {
            if ($model->getDateModified() <> $model->getDateCreated()) {
                return  new Label()
                       ->attributes(['class' => 'label label-danger'])
                       ->content(
                    Html::encode($model->getDateModified()->format('Y-m-d')));
            } else {
                return  new Label()
                       ->attributes(['class' => LABEL_SUCCESS])
                       ->content(
                    Html::encode($model->getDateModified()->format('Y-m-d')));
            }
        },
        encodeContent: false,
        visible: $visible,
    ),
    new DataColumn(
        'date_due',
        header: $translator->translate('due.date'),
        content: static function (Inv $model): Label {
            $now = new \DateTimeImmutable('now');
            return  new Label()
                    ->attributes(
                        [
                            'class' => $model->getDateDue() > $now
                            ? LABEL_SUCCESS : LABEL_WARNING])
                    ->content(Html::encode(
                            !is_string($dateDue = $model->getDateDue())
                            ? $dateDue->format('Y-m-d') : ''));
        },
        encodeContent: false,
        withSorting: true,
        visible: $visible,
    ),
    new DataColumn(
        property: 'filterInvAmountTotal',
        header: $translator->translate('total')
            . '➡️' . $s->getSetting('currency_symbol'),
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountTotal = $model->getInvAmount()->getTotal();
            return
                 new Label()
                    ->attributes([
                        'class' => $invAmountTotal > 0.00
                            ? LABEL_SUCCESS : LABEL_WARNING])
                    ->content(Html::encode(null !== $invAmountTotal
                            ? number_format($invAmountTotal, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'id'         => 'filter-amount-total',
                    'class'      => INV_AMOUNT_FILTER_CLASS,
                    'aria-label' => 'Filter by total amount',
                    'title'      => $translator->translate('total'),
                    'placeholder' => $translator->translate('total'),
                ]),
        withSorting: false,
        footer:  new Span()
                ->addClass('inv-footer-amount')
                ->addAttributes(['style' => INV_AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $translator->translate('total') . ':', ['class' => 'inv-footer-label'])
                    . ' ' . $s->getSetting('currency_symbol')
                    . ' ' . number_format($totalAmount, $decimalPlaces)
                )->encode(false)->render(),
    ),
    new DataColumn(
        property: 'filterInvAmountPaid',
        header: $translator->translate('paid')
                                    . '➡️' . $s->getSetting('currency_symbol'),
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountPaid = $model->getInvAmount()->getPaid();
            if (null !== $invAmountPaid) {
                $paidValue = $invAmountPaid > 0.00 ? $invAmountPaid : 0.00;
            } else {
                $paidValue = 0.00;
            }

            return new Label()
                    ->attributes([
                        'class' =>
        $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal()
                            ? 'label label-danger' : LABEL_SUCCESS])
                    ->content(Html::encode(number_format($paidValue, $decimalPlaces)));
        },
        encodeContent: false,
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-amount-paid',
                    'class'       => INV_AMOUNT_FILTER_CLASS,
                    'aria-label'  => 'Filter by paid amount',
                    'title'       => $translator->translate('paid'),
                    'placeholder' => $translator->translate('paid'),
                ]),
        withSorting: false,
        footer:  new Span()
                ->addClass('inv-footer-amount')
                ->addAttributes(['style' => INV_AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $translator->translate('paid') . ':', ['class' => 'inv-footer-label'])
                    . ' ' . $s->getSetting('currency_symbol')
                    . ' ' . number_format($totalPaid, $decimalPlaces)
                )->encode(false)->render(),
    ),
    new DataColumn(
        property: 'filterInvAmountBalance',
        header: $translator->translate('balance')
                                    . '➡️' . $s->getSetting('currency_symbol'),
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountBalance = $model->getInvAmount()->getBalance();
            if (null !== $invAmountBalance) {
                $balanceValue = $invAmountBalance > 0.00 ? $invAmountBalance : 0.00;
            } else {
                $balanceValue = 0.00;
            }

            return new Label()
                    ->attributes([
                         'class' => $invAmountBalance > 0.00 ?
                                LABEL_SUCCESS : LABEL_WARNING])
                    ->content(Html::encode(number_format($balanceValue, $decimalPlaces)));
        },
        encodeContent: false,
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'id'          => 'filter-amount-balance',
                    'class'       => INV_AMOUNT_FILTER_CLASS,
                    'aria-label'  => 'Filter by balance amount',
                    'title'       => $translator->translate('balance'),
                    'placeholder' => $translator->translate('balance'),
                ]),
        withSorting: false,
        footer:  new Span()
                ->addClass('inv-footer-amount')
                ->addAttributes(['style' => INV_AMOUNT_FILTER_STYLE])
                ->content(
                    Html::tag('small', $translator->translate('balance') . ':', ['class' => 'inv-footer-label'])
                    . ' ' . $s->getSetting('currency_symbol')
                    . ' ' . number_format($totalBalance, $decimalPlaces)
                )->encode(false)->render(),
    ),
    new DataColumn(
        header: '🚚',
        content: static function (Inv $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '', [
                'class' => 'bi-plus']), $urlGenerator->generate(
                'del/add',
                [
/**
 *
 * Related logic: see DeliveryLocation add function getRedirectResponse
 * Related logic: see config/common/routes/routes.php Route::methods([Method::GET,
 *  Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
 */
                    'client_id' => $model->getClientId(),
                ],
                [
                    'origin' => 'inv',
                    'origin_id' => $model->getId(),
                    'action' => 'index',
                ],
            ));
        },
        encodeContent: false,
        visible: $visible,
        withSorting: false,
    ),
    new DataColumn(
        'quote_id',
        header: $translator->translate('quote.number.status'),
        content: static function (Inv $model) use ($urlGenerator, $qR): string|A {
            $quote_id = $model->getQuoteId();
            $quote = $qR->repoQuoteUnloadedquery($quote_id);
            if (null !== $quote) {
                $statusId = $quote->getStatusId();
                if (null !== $statusId) {
                    return Html::a(
                        ($quote->getNumber() ?? '#')
                        . ' '
                        . $qR->getSpecificStatusArrayLabel((string) $statusId),
                        $urlGenerator->generate('quote/view', ['id' => $quote_id]),
                        [
                            'style' => 'text-decoration:none',
                            'class' => 'label '
                            . $qR->getSpecificStatusArrayClass((string) $statusId),
                        ],
                    );
                }
            }
            return '';
        },
        visible: $visible,
        withSorting: false,
    ),
    new DataColumn(
        'so_id',
        header: $translator->translate('salesorder.number.status'),
        content: static function (Inv $model) use ($urlGenerator, $soR): string|A {
            $so_id = $model->getSoId();
            $so = $soR->repoSalesOrderUnloadedquery($so_id);
            if (null !== $so) {
                $statusId = $so->getStatusId();
                if (null !== $statusId) {
                    return Html::a(($so->getNumber() ?? '#')
                     . ' ' . $soR->getSpecificStatusArrayLabel((string) $statusId),
                      $urlGenerator->generate('salesorder/view', ['id' => $so_id]),
                       ['style' => 'text-decoration:none', 'class' => 'label '
                        . $soR->getSpecificStatusArrayClass($statusId)]);
                }
            } else {
                return '';
            }
            return '';
        },
        visible: $visible,
        withSorting: false,
    ),
    new DataColumn(
        'delivery_location_id',
        header: $translator->translate('delivery.location.global.location.number'),
        content: static function (Inv $model) use ($dlR): string {
            $delivery_location_id = $model->getDeliveryLocationId();
            $delivery_location = (($dlR->repoCount($delivery_location_id) > 0) ? $dlR->repoDeliveryLocationquery($delivery_location_id) : null);
            return null !== $delivery_location ? Html::encode($delivery_location->getGlobalLocationNumber()) : '';
        },
        encodeContent: false,
        visible: $visible,
        withSorting: false,
    ),
    new ActionColumn(
        header: '🗑️',
        before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
        after: Html::closeTag('div'),
        buttons: [
            new ActionButton(
                content: '🗑️',
                url: static function (Inv $model) use ($s, $urlGenerator): string {
                    return $model->getIsReadOnly() === false
                            && $s->getSetting('disable_read_only')
                            === (string) 0
                            && $model->getSoId() === '0'
                            && $model->getQuoteId() === '0'
                        ? $urlGenerator->generate('inv/delete', [
                            'id' => $model->getId()])
                        : '';
                },
                attributes: static function (Inv $model)
                                                    use ($s, $translator): array {
                    if ($model->getIsReadOnly() === false
                            && $s->getSetting('disable_read_only') === (string) 0
                            && $model->getSoId() === '0'
                            && $model->getQuoteId() === '0') {
                        return [
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate('delete'),
                            'class' => 'btn btn-outline-danger btn-sm',
                            'onclick' => "return confirm("
                            . (string) json_encode($translator->translate('delete.record.warning'))
                            . ");"
                        ];
                    } else {
                        return [
                            'data-bs-toggle' => 'tooltip',
                            'title' => $translator->translate('delete'),
                            'disabled' => 'disabled',
                            'aria-disabled' => 'true',
                            'class' => 'btn btn-secondary btn-sm disabled',
                            'style' => 'pointer-events:none'
                        ];
                    }
                },
            ),
        ],
        visible: $visible,
    ),
];

$toolbarString
    =  new Form()->post($urlGenerator->generate(INV_ROUTE_INDEX))->csrf($csrf)->open()
    .  new Div()->addClass('float-start')->content(
         new H4()
            ->addClass('me-3 d-inline-block')
            ->content($translator->translate('invoice'))
        . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
        . $allVisible
        . $toolbarReset
        //. Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('client'), false)
        . $copyInvoiceMultiple
        . $markAsSent
        . ($s->getSetting('disable_read_only') === (string) 0
            ? $disabledMarkSentAsDraft
            : $enabledMarkSentAsDraft
        )
        . $markAsRecurringMultiple
        . ($clientCount == 0 ? $disabledAddInvoiceButton : $enabledAddInvoiceButton)
        . Html::closeTag('div')
        .  new Div()
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                 new Label()
                    ->addClass(
                        'btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $translator->translate('group.by') . ':')
                .
                 new Select()
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style' => 'max-width: 150px;',
                        'data-base-url' => $urlGenerator->generate(INV_ROUTE_INDEX),
                    ])
                    ->optionsData([
                        'none' => $translator->translate('grouping.none'),
                        'status' => $translator->translate('status'),
                        'client' => $translator->translate('client'),
                        'client_group' => $translator->translate('client.group'),
                        'month' => $translator->translate('month'),
                        'year' => $translator->translate('year'),
                        'date' => $translator->translate('date'),
                        'amount_range' => 'Amount Range'
                    ])
                    ->value($groupBy)
            )
            ->encode(false)
            ->render()
        . ($enableGrouping ?
             new Div()
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                     new HtmlButton()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(false)',
                            'title' => 'Collapse All Groups'
                        ])
                        ->content( new I()->addClass('bi bi-chevron-up')) .
                     new HtmlButton()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(true)',
                            'title' => 'Expand All Groups'
                        ])
                        ->content( new I()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render() : ''
        )
    )->encode(false)->render()
    .  new Form()->close();

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('invoices'),
    $label,
);

// Add left-aligned wrapper when additional
// columns are visible to accommodate more columns
$tableOrTableResponsive = $visible ? 'table-responsive' : 'table';

if ($visible) {
    echo '<div class="text-start">';
}

// Row Grouping Implementation
// You can change the grouping field by modifying the
// getGroupValue function
$previousGroupValue = '';

// Function to get group value based on selected field
$getGroupValue = static function (Inv $invoice) use ($groupBy, $iR): string {
    return match ($groupBy) {
        'client' => $invoice->getClient()?->getClientFullName()
                                                            ?? 'Unknown Client',
        'status' => $iR->getSpecificStatusArrayLabel(
                                             (string) $invoice->getStatusId()),
        'month' => $invoice->getDateCreated()->format('Y-m'),
        'year' => $invoice->getDateCreated()->format('Y'),
        'date' => $invoice->getDateCreated()->format('Y-m-d'),
        'client_group' => $invoice->getClient()?->getClientGroup() ?? 'No Group',
        'amount_range' => match (true) {
            ($invoice->getInvAmount()->getTotal() ?? 0) < 100 => '< $100',
            ($invoice->getInvAmount()->getTotal() ?? 0) < 500 => '$100 - $500',
            ($invoice->getInvAmount()->getTotal() ?? 0) < 1000 => '$500 - $1000',
            default => '> $1000'
        },
        default => 'No Group'
    };
};

$columnCount = count(array_filter($columns,
    fn(ColumnInterface $col) => $col->isVisible()
));

// Calculate totals per group (only if grouping is enabled)
$groupTotals = [];
if ($enableGrouping) {
    /**
     * @var App\Invoice\Entity\Inv $invoice
     */
    foreach ($sortedAndPagedPaginator->read() as $invoice) {
        $groupValue = $getGroupValue($invoice);
        if (!isset($groupTotals[$groupValue])) {
            $groupTotals[$groupValue] = [
                'count' => 0,
                'total' => 0.00,
                'paid' => 0.00,
                'balance' => 0.00
            ];
        }
        $groupTotals[$groupValue]['count']++;
        $groupTotals[$groupValue]['total'] += $invoice->getInvAmount()->getTotal()
                                                                        ?? 0.00;
        $groupTotals[$groupValue]['paid'] += $invoice->getInvAmount()->getPaid()
                                                                        ?? 0.00;
        $groupTotals[$groupValue]['balance'] += $invoice->getInvAmount()->getBalance()
                                                                        ?? 0.00;
    }
}

$gridView = GridView::widget()
// unpack the contents within the array using the three dot splat operator
->bodyRowAttributes(['class' => 'align-left'])
->tableAttributes(['class' => $tableOrTableResponsive
            . ' table-bordered table-striped h-75', 'id' => 'table-invoice'])
->columns(...$columns)
->columnGrouping(true); // Enable HTML column grouping for better styling

// Apply grouping only if enabled
if ($enableGrouping) {
    $gridView = $gridView->beforeRow(
                static function (array|object $invoice) use (
        &$previousGroupValue,
        $getGroupValue,
        $groupTotals,
        $decimalPlaces,
        $groupBy,
        $s,
        $columnCount
    ): ?\Yiisoft\Html\Tag\Tr {
        // Ensure the invoice is of the expected type
        assert($invoice instanceof Inv);
        $currentGroupValue = $getGroupValue($invoice);
        
        if ($previousGroupValue !== $currentGroupValue) {
            $previousGroupValue = $currentGroupValue;
            $groupData = $groupTotals[$currentGroupValue];
            $currencySymbol = $s->getSetting('currency_symbol');

            return \Yiisoft\Html\Html::tr()
                ->addClass(
               'group-header bg-secondary text-white fw-bold group-collapsible')
                ->addAttributes(['onclick' => 'toggleGroupRows(this)'])
                ->cells(
                    \Yiisoft\Html\Html::td()
                        ->addAttributes(['colspan' => (string) $columnCount])
                        ->addClass('p-3')
                        ->content(
'<div class="d-flex justify-content-between align-items-center">' .
'<div>' .
'<i class="bi bi-chevron-down me-2 group-toggle-icon"></i>' .
'<i class="bi bi-folder2-open me-2"></i>' .
'<span class="fs-5">' . Html::encode(ucfirst($groupBy))
                      . ': ' . Html::encode($currentGroupValue) . '</span>' .
'<span class="badge bg-primary ms-2">'
                             . $groupData['count']
                             . ' invoice'
                             . ($groupData['count'] === 1 ? '' : 's')
                             . '</span>'
                             . '</div>'
                             . '<div class="text-end">'
                             . '<small class="d-block">Total: <strong>'
                             . number_format($groupData['total'], $decimalPlaces)
                             . ' '
                             . $currencySymbol
                             . '</strong></small>'
                             . '<small class="d-block">Paid: <strong>'
                             . number_format($groupData['paid'], $decimalPlaces)
                             . ' '
                             . $currencySymbol
                             . '</strong></small>'
                             . '<small class="d-block">Balance: <strong>'
                             . number_format($groupData['balance'],
                                     $decimalPlaces)
                             . ' '
                             . $currencySymbol
                             . '</strong></small>'
                             . '</div>'
                             . '</div>'
                        )
                        ->encode(false)
                );
        }
        return null;
    });
}

echo $gridView
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be
// sorted. It also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->footerRowAttributes(['class' => 'card-footer bg-success text-white fw-bold'])
->enableFooter(true)
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->id('w3-grid')
->summaryAttributes([
  'class' => 'mt-3 me-3 summary d-flex justify-content-between align-items-center'])
->summaryTemplate('<div class="d-flex align-items-center">'
        . $pageSizeLimiter::buttons(
            $currentRoute, $s, $translator, $urlGenerator, 'inv')
        . ' ' . $gridSummary . '</div>')
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

// Close the left-aligned wrapper div when additional columns are visible
if ($visible) {
    echo '</div>';
}

echo $modal_add_inv;
echo $modal_create_recurring_multiple;
echo $modal_copy_inv_multiple;
?>

<!-- Angular Amount Magnifier Integration -->

<div id="angular-amount-magnifier-app">
    <app-root></app-root>
</div>

<?php
$magnifierScript = <<<JS
// Initialize Angular Amount Magnifier when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Import and initialize the amount magnifier service
    class InvoiceAmountMagnifier {
        constructor() {
            this.magnificationFactor = 1.4;
            this.animationDuration = 250;
            this.initialize();
        }

        initialize() {
            this.attachMagnifiersToAmounts();
            this.setupMutationObserver();
        }

        attachMagnifiersToAmounts() {
            const amountSelectors = [
                '.label.label-success',
                '.label.label-warning',
                '.label.label-danger'
            ];

            amountSelectors.forEach(selector => {
                const elements = document.querySelectorAll(selector);
                elements.forEach((element) => {
                    if (this.isAmountElement(element)
                        && !element.hasAttribute('data-magnifier-initialized')) {
                        this.addMagnificationBehavior(element);
                        element.setAttribute('data-magnifier-initialized', 'true');
                    }
                });
            });
        }

        isAmountElement(element) {
            const text = element.textContent?.trim() || '';
            const amountPattern = /^[\d,]+\.?\d*$/;
            return amountPattern.test(text) && text.length > 0;
        }

        addMagnificationBehavior(element) {
            let borderColor = '#007bff';
            let bgColor = 'rgba(255, 255, 255, 0.95)';
            
            if (element.classList.contains('label-success')) {
                borderColor = '#28a745';
                bgColor = '#d4edda';
            } else if (element.classList.contains('label-warning')) {
                borderColor = '#ffc107';
                bgColor = '#fff3cd';
            } else if (element.classList.contains('label-danger')) {
                borderColor = '#dc3545';
                bgColor = '#f8d7da';
            }

            const computedStyle = window.getComputedStyle(element);
            const originalStyles = {
                fontSize: computedStyle.fontSize,
                fontWeight: computedStyle.fontWeight,
                backgroundColor: computedStyle.backgroundColor,
                border: computedStyle.border,
                borderRadius: computedStyle.borderRadius,
                padding: computedStyle.padding,
                zIndex: computedStyle.zIndex,
                position: computedStyle.position,
                transform: computedStyle.transform,
                boxShadow: computedStyle.boxShadow
            };

            element.style.transition = `all \${this.animationDuration}ms ease-in-out`;
            element.style.cursor = 'pointer';
            element.classList.add('amount-magnifiable');

            let isHovered = false;

            element.addEventListener('mouseenter', () => {
                if (!isHovered) {
                    isHovered = true;
                    this.applyMagnification(element, originalStyles, borderColor,
                        bgColor);
                }
            });

            element.addEventListener('mouseleave', () => {
                if (isHovered) {
                    isHovered = false;
                    this.removeMagnification(element, originalStyles);
                }
            });

            element.addEventListener('click', (e) => {
                e.preventDefault();
                if (isHovered) {
                    this.removeMagnification(element, originalStyles);
                    isHovered = false;
                } else {
                    this.applyMagnification(element, originalStyles, borderColor,
                        bgColor);
                    isHovered = true;
                }
            });
        }

        applyMagnification(element, originalStyles, borderColor, bgColor) {
            const currentFontSize = parseFloat(originalStyles.fontSize);
            const newFontSize = currentFontSize * this.magnificationFactor;
            
            element.style.fontSize = `\${newFontSize}px`;
            element.style.fontWeight = 'bold';
            element.style.backgroundColor = bgColor;
            element.style.border = `2px solid \${borderColor}`;
            element.style.borderRadius = '6px';
            element.style.padding = '8px 12px';
            element.style.zIndex = '1000';
            element.style.position = 'relative';
            element.style.transform = 'scale(1.1)';
            element.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        }

        removeMagnification(element, originalStyles) {
            Object.keys(originalStyles).forEach(property => {
                element.style[property] = originalStyles[property];
            });
        }

        setupMutationObserver() {
            this.observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList'
                            && mutation.addedNodes.length > 0) {
                        setTimeout(() => {
                            this.attachMagnifiersToAmounts();
                        }, 100);
                    }
                });
            });

            const tableContainer = document.getElementById('table-invoice')
                    || document.querySelector('.table-responsive');
            if (!tableContainer) return;
            this.observer.observe(tableContainer, {
                childList: true,
                subtree: true
            });
        }
    }

    new InvoiceAmountMagnifier();

    // Group-by select — safe whitelist-validated navigation
    const groupBySelect = document.querySelector('.group-by-select');
    if (groupBySelect) {
        const allowed = ['none', 'status', 'client', 'client_group', 'month',
                         'year', 'date', 'amount_range'];
        groupBySelect.addEventListener('change', function() {
            if (allowed.includes(this.value)) {
                const baseUrl = this.dataset.baseUrl || '';
                window.location.href = baseUrl + '?groupBy='
                        + encodeURIComponent(this.value);
            }
        });
    }
});
JS;

$magnifierStyle = <<<CSS
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

/* Status column tooltip font size - 2x larger */
.label[data-bs-toggle="tooltip"] + .tooltip .tooltip-inner,
.tooltip.show .tooltip-inner {
    font-size: 2em !important;
}

/* ── Distinguishable filter dropdowns (all viewports, essential on mobile) ── */
.inv-filter {
    border-left: 4px solid transparent;
    border-radius: 4px;
    padding-left: 6px;
    font-size: 1rem;
    max-width: 160px;
    font-weight: 500;
}

/* Colour-coded left border per filter */
#filter-inv-number        { border-left-color: #0d6efd; } /* blue   – invoice #      */
#filter-credit-inv-number { border-left-color: #6610f2; } /* indigo – credit note   */
#filter-family-name       { border-left-color: #6f42c1; } /* purple – family        */
#filter-year-month   { border-left-color: #fd7e14; } /* orange – date      */
#filter-status       { border-left-color: #198754; } /* green  – status    */
#filter-client       { border-left-color: #0dcaf0; } /* cyan   – client    */
#filter-client-group { border-left-color: #dc3545; } /* red    – group     */
#filter-address-1    { border-left-color: #d63384; } /* pink   – address   */

/* ── Footer row: hide empty cells, compact layout on mobile ── */

/* Desktop: label above the value, right-aligned */
.inv-footer-label {
    display: block;
    font-size: 0.7rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    opacity: 0.8;
}
.inv-footer-amount {
    font-size: 1rem;
}

@media (max-width: 767.98px) {
    /* Collapse empty footer cells so only the three amounts show */
    tfoot tr td:not(:has(.inv-footer-amount)) {
        display: none;
        padding: 0;
        border: none;
    }
    /* Lay out the three amount cells as a flex row */
    tfoot tr {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        padding: 8px;
    }
    tfoot tr td:has(.inv-footer-amount) {
        flex: 1 1 28%;
        min-width: 90px;
        text-align: center;
        border-radius: 6px;
        padding: 6px 10px;
        background: rgba(255,255,255,0.15);
    }
    .inv-footer-label {
        font-size: 0.72rem;
    }
    .inv-footer-amount {
        font-size: 1.1rem;
        font-weight: 700;
    }
}

/* ── Amount text input filter ── */
.inv-amount-filter {
    border-left: 4px solid #20c997; /* teal – amount */
    border-radius: 4px;
    padding: 4px 8px;
    font-size: 1rem;
    font-weight: 500;
    max-width: 110px;
    width: 100%;
    text-align: right;
}

#filter-amount-total   { border-left-color: #20c997; } /* teal   – total   */
#filter-amount-paid    { border-left-color: #198754; } /* green  – paid    */
#filter-amount-balance { border-left-color: #ffc107; } /* amber  – balance */

/* On small screens show the filter below its column header as a full-width block */
@media (max-width: 767.98px) {
    .inv-filter {
        display: block;
        width: 100%;
        max-width: 100%;
        font-size: 1.1rem;
        padding: 8px 10px;
        border-left-width: 5px;
    }
    .inv-amount-filter {
        display: block;
        width: 100%;
        max-width: 100%;
        font-size: 1.1rem;
        padding: 8px 10px;
        border-left-width: 5px;
        text-align: left;
    }
}
CSS;

echo Html::script($magnifierScript)->type('module');
echo Html::style($magnifierStyle);

// Inject translated prompt text into each filter's first (empty) option.
// json_encode ensures the strings are safely embedded inside the JS literal.
$filterPromptLabels = json_encode([
    'filter-inv-number'        => '— ' . $translator->translate('number')      . ' —',
    'filter-credit-inv-number' => '— ' . $translator->translate(
        'credit.invoice.for.invoice') . ' —',
    'filter-family-name'       => '— ' . $translator->translate('family.name') . ' —',
    'filter-year-month'   => '— ' . $translator->translate(
        'datetime.immutable.date.created.mySql.format.year.month.filter')  . ' —',
    'filter-status'       => '— ' . $translator->translate('status')      . ' —',
    'filter-client'       => '— ' . $translator->translate('client')      . ' —',
    'filter-client-group' => '— ' . $translator->translate('client.group') . ' —',
], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

$filterPromptScript = <<<JS
document.addEventListener('DOMContentLoaded', function () {
    const labels = {$filterPromptLabels};
    Object.entries(labels).forEach(([id, label]) => {
        const sel = document.getElementById(id);
        if (sel && sel.options.length > 0) {
            sel.options[0].text = label;
        }
    });
});
JS;
echo Html::script($filterPromptScript)->type('module');

if ($enableGrouping):
    $groupingScript = <<<JS
// Group collapsible functionality
window.toggleGroupRows = function(groupHeader) {
    const toggleIcon = groupHeader.querySelector('.group-toggle-icon');
    let nextRow = groupHeader.nextElementSibling;
    let isCollapsing = toggleIcon.classList.contains('bi-chevron-down');
    
    // Toggle icon
    if (isCollapsing) {
        toggleIcon.classList.remove('bi-chevron-down');
        toggleIcon.classList.add('bi-chevron-right');
    } else {
        toggleIcon.classList.remove('bi-chevron-right');
        toggleIcon.classList.add('bi-chevron-down');
    }
    
    // Toggle all rows until next group header or end of table
    while (nextRow && !nextRow.classList.contains('group-header')) {
        if (isCollapsing) {
            nextRow.style.display = 'none';
        } else {
            nextRow.style.display = '';
        }
        nextRow = nextRow.nextElementSibling;
    }
};

// Add expand/collapse all functionality
window.toggleAllGroups = function(expand = null) {
        const groupHeaders = document.querySelectorAll('.group-header');
        groupHeaders.forEach(header => {
            const toggleIcon = header.querySelector('.group-toggle-icon');
            const isCurrentlyCollapsed =
                            toggleIcon.classList.contains('bi-chevron-right');
            
            if (expand === null) {
                // Toggle current state
                window.toggleGroupRows(header);
            } else if (expand && isCurrentlyCollapsed) {
                // Expand if currently collapsed
                window.toggleGroupRows(header);
            } else if (!expand && !isCurrentlyCollapsed) {
                // Collapse if currently expanded
                window.toggleGroupRows(header);
            }
        });
    };
JS;

    $groupingStyle = <<<CSS
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
CSS;

    echo Html::script($groupingScript)->type('module');
    echo Html::style($groupingStyle);
endif;

$mobilePreviewScript = <<<JS
// Mobile/Desktop Preview Toggle
// Renders the current page inside a 390 px phone-frame overlay (Android standard).
// Suppressed when running inside the preview iframe itself.
class MobilePreviewToggle {
    constructor() {
        if (window.self !== window.top) return;
        this.isActive = false;
        this.toggleBtn = null;
        this.injectStyles();
        this.createButton();
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isActive) this.deactivate();
        });
    }

    injectStyles() {
        if (document.getElementById('mp-styles')) return;
        const s = document.createElement('style');
        s.id = 'mp-styles';
        s.textContent =
            '.mp-btn{position:fixed;bottom:72px;right:20px;z-index:10001;' +
            'padding:9px 18px;background:#212529;color:#fff;' +
            'border:2px solid #495057;border-radius:22px;cursor:pointer;' +
            'font-size:13px;font-weight:600;' +
            'box-shadow:0 4px 14px rgba(0,0,0,.35);' +
            'transition:background .2s,transform .15s;}' +
            '.mp-btn:hover{background:#495057;transform:translateY(-2px);}' +
            '.mp-btn.mp-on{background:#0d6efd;border-color:#0d6efd;}' +
            '#mp-overlay{display:none;position:fixed;inset:0;z-index:10000;' +
            'background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);' +
            'overflow-y:auto;justify-content:center;' +
            'align-items:flex-start;padding:30px 0 60px;}' +
            '#mp-overlay.mp-show{display:flex;}' +
            '#mp-phone{width:390px;background:#fff;border-radius:48px;' +
            'box-shadow:inset 0 0 0 2px #555,0 0 0 10px #1c1c1e,' +
            '0 0 0 12px #3a3a3c,0 40px 100px rgba(0,0,0,.6);' +
            'overflow:hidden;position:relative;flex-shrink:0;}' +
            '#mp-badge{position:absolute;top:-28px;left:50%;' +
            'transform:translateX(-50%);background:rgba(0,0,0,.6);' +
            'color:#a0cfff;font-size:11px;padding:2px 10px;' +
            'border-radius:10px;white-space:nowrap;}' +
            '#mp-notch-bar{background:#1c1c1e;height:34px;' +
            'display:flex;align-items:center;justify-content:center;}' +
            '#mp-notch{width:110px;height:24px;background:#1c1c1e;' +
            'border-radius:0 0 16px 16px;}' +
            '#mp-iframe{width:390px;height:800px;border:none;display:block;}' +
            '#mp-home-bar{background:#1c1c1e;height:28px;' +
            'display:flex;align-items:center;justify-content:center;}' +
            '#mp-home-ind{width:120px;height:5px;background:#555;border-radius:3px;}' +
            '#mp-hint{position:fixed;bottom:18px;left:50%;' +
            'transform:translateX(-50%);z-index:10002;' +
            'color:rgba(255,255,255,.45);font-size:12px;' +
            'pointer-events:none;white-space:nowrap;}';
        document.head.appendChild(s);
    }

    createButton() {
        this.toggleBtn = document.createElement('button');
        this.toggleBtn.className = 'mp-btn';
        this.toggleBtn.textContent = '📱 Mobile Preview';
        this.toggleBtn.title = 'Preview at Android 390 px width';
        this.toggleBtn.addEventListener('click', () => this.toggle());
        document.body.appendChild(this.toggleBtn);
    }

    buildOverlay() {
        if (document.getElementById('mp-overlay')) return;
        const overlay = document.createElement('div');
        overlay.id = 'mp-overlay';
        const phone = document.createElement('div');
        phone.id = 'mp-phone';
        const badge = document.createElement('div');
        badge.id = 'mp-badge';
        badge.textContent = '📱 Android — 390 × 844 px';
        phone.appendChild(badge);
        const notchBar = document.createElement('div');
        notchBar.id = 'mp-notch-bar';
        const notch = document.createElement('div');
        notch.id = 'mp-notch';
        notchBar.appendChild(notch);
        phone.appendChild(notchBar);
        const iframe = document.createElement('iframe');
        iframe.id = 'mp-iframe';
        iframe.src = window.location.href;
        phone.appendChild(iframe);
        const homeBar = document.createElement('div');
        homeBar.id = 'mp-home-bar';
        const homeInd = document.createElement('div');
        homeInd.id = 'mp-home-ind';
        homeBar.appendChild(homeInd);
        phone.appendChild(homeBar);
        overlay.appendChild(phone);
        const hint = document.createElement('div');
        hint.id = 'mp-hint';
        hint.textContent = 'Press Esc or click 🖥️ Desktop View to exit';
        overlay.appendChild(hint);
        document.body.appendChild(overlay);
    }

    activate() {
        this.isActive = true;
        this.buildOverlay();
        document.getElementById('mp-overlay')?.classList.add('mp-show');
        this.toggleBtn.textContent = '🖥️ Desktop View';
        this.toggleBtn.classList.add('mp-on');
    }

    deactivate() {
        this.isActive = false;
        document.getElementById('mp-overlay')?.classList.remove('mp-show');
        this.toggleBtn.textContent = '📱 Mobile Preview';
        this.toggleBtn.classList.remove('mp-on');
    }

    toggle() {
        this.isActive ? this.deactivate() : this.activate();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new MobilePreviewToggle();
});
JS;

echo Html::script($mobilePreviewScript)->type('module');
