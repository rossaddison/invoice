<?php

declare(strict_types=1);

use App\Invoice\Entity\Quote;
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
use Yiisoft\Html\Tag\Input;
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

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $quotes
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Data\Paginator\OffsetPaginator $sortedAndPagedPaginator
 * @var Yiisoft\Data\Reader\Sort $sort
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var bool $visible
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var array $quoteStatuses
 * @var string $alert
 * @var string $csrf
 * @var string $groupBy
 * @var string $label
 * @var string $modal_add_quote
 * @var string $sortString
 * @var string $status
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataQuoteNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatusDropDownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
$quoteIndex = 'quote/index';
$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-primary me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? $quoteIndex))
    ->id('btn-reset')
    ->render();

$allVisible =  new A()
    ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('hide.or.unhide.columns')])
    ->addClass('btn btn-warning me-1 ajax-loader')
    ->content('↔️')
    ->href($urlGenerator->generate('setting/visible', ['origin' => 'quote']))
    ->id('btn-all-visible')
    ->render();

$enabledAddQuoteButton =  new A()
    ->addAttributes([
        'class' => 'btn',
        'data-bs-toggle' => 'modal',
        'style' => 'text-decoration:none; background-color: #ffffff !important;'
        . ' border: 2px solid #b19cd9 !important; color: #b19cd9 !important;'
        . ' font-weight: 500;',
    ])
    ->content('➕')
    ->href('#modal-add-quote')
    ->id('btn-enabled-quote-add-button')
    ->render();

$disabledAddQuoteButton =  new A()
    ->addAttributes([
        'class' => 'btn',
        'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('add.client'),
        'disabled' => 'disabled',
        'style' => 'text-decoration:none; background-color: #ffffff !important;'
        . ' border: 2px solid #b19cd9 !important; color: #b19cd9 !important;'
        . ' font-weight: 500; opacity: 0.5;',
    ])
    ->content('➕')
    ->href('#modal-add-quote')
    ->id('btn-disabled-quote-add-button')
    ->render();

$enableGrouping = $groupBy !== 'none';

$sort = Sort::only([
    'id',
    'status_id',
    'number',
    'date_created',
    'date_expires',
    'client_id'
])
// (Related logic: see vendor\yiisoft\data\src\Reader\Sort
// - => 'desc'  so -id => default descending on id
->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($quotes))
    ->withPageSize(
        $defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1
    )
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

// Calculate totals for footer (from paginator to avoid exhausting $quotes)
$totalAmount = 0.0;

/**
 * @var Quote $quote
 */
foreach ($sortedAndPagedPaginator->read() as $quote) {
    $totalAmount += null !== ($total = $quote->getQuoteAmount()->getTotal())
            ? $total : 0.00;
}
$settingTabindex = 'setting/tabIndex';
echo Breadcrumbs::widget()
     ->links(
         BreadcrumbLink::to(
             label: $translator->translate('default.quote.group'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_group]',
             ),
             active: true,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $defaultQuoteGroup ??
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.notes'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_notes]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('default.quote.notes') ?:
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('quotes.expire.after'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[quotes_expire_after]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('quotes_expire_after') ?:
                 $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('generate.quote.number.for.draft'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[generate_quote_number_for_draft]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('generate_quote_number_for_draft')
                 == '1' ? '✅' : '❌',
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.email.template'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[email_quote_template]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => strlen($s->getSetting('email_quote_template')) > 0 ?
                    $s->getSetting('email_quote_template')
                    : $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('pdf.quote.footer'),
             url: $urlGenerator->generate(
                 $settingTabindex,
                 [],
                 ['active' => 'quotes'],
                 'settings[pdf_quote_footer]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('pdf_quote_footer') ?:
                    $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
     )
     ->listId(false)
     ->render();

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
            $quote = $context->data;
            if (($quote instanceof Quote) && (null !== ($id = $quote->getId()))) {
                return  $input
                        ->addAttributes([
                           'id' => $id,
                           'name' => 'checkbox[]',
                           'data-bs-toggle' => 'tooltip',
                           'title' => $quote->getQuoteAmount()->getTotal() == 0
                               ? $translator->translate(
                               'index.checkbox.add.some.items.to.enable') : ''])
                       ->value($id)
                       ->disabled($quote->getQuoteAmount()->getTotal() > 0 ?
                               false : true)
                       ->render();
            }
            return '';
        },
        multiple: true,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (Quote $model): string {
            return (string) $model->getId();
        },
        withSorting: true,
    ),
    new ActionColumn(
        before: Html::openTag('div', ['class' => 'btn-group', 'role' => 'group']),
        after: Html::closeTag('div'),
        buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Quote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('quote/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
                'class' => 'btn btn-outline-primary btn-sm',
            ],
        ),
        new ActionButton(
            content: '✎',
            url: static function (Quote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('quote/edit', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('edit'),
                'class' => 'btn btn-outline-warning btn-sm',
            ],
        ),
        new ActionButton(
            content: static function (Quote $model): string {
                return ($model->getSoId() == 0) && ($model->getInvId() == 0)
                ? '❌'
                : '🚫';
            },
            url: static function (Quote $model) use ($urlGenerator): string {
                return ($model->getSoId() == 0) && ($model->getInvId() == 0)
                ? $urlGenerator->generate('quote/delete', ['id' => $model->getId()])
                : '';
            },
            attributes: static function (Quote $model) use ($translator): array {
                return ($model->getSoId() == 0) && ($model->getInvId() == 0)
                ?
                [
                    'onclick' => "return confirm("
                    . (string) json_encode($translator->translate('delete.record.warning'))
                    . ");",

                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('delete.quote.single'),
                    'class' => 'btn btn-outline-danger btn-sm',
                ]
                : [
                    'disabled' => true,
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('delete.quote.derived'),
                    'class' => 'btn btn-secondary btn-sm disabled',
                ];
            },
        ),
    ]),
    new DataColumn(
        property: 'filterStatus',
        header: '<span data-bs-toggle="tooltip" data-bs-html="false" title="' .
                Html::encode('🌎 ' . $translator->translate('all') . '<br/>🗋 '
                . $translator->translate('draft') . '<br/>📨 '
                . $translator->translate('sent') . '<br/>👀 '
                . $translator->translate('viewed') . '<br/>✅ '
                . $translator->translate('approved') . '<br/>❌ '
                . $translator->translate('rejected') . '<br/>🚫 '
                . $translator->translate('canceled')) . '">📊 '
                . $translator->translate('status') . '</span>',
        encodeHeader: false,
        content: static function (Quote $model) use ($qR): string {
            $statusId = $model->getStatusId();
            if ($statusId === null) {
                return '<span class="badge text-bg-secondary">N/A</span>';
            }
            $label = $qR->getSpecificStatusArrayLabel((string) $statusId);
            $class = $qR->getSpecificStatusArrayClass((string) $statusId);

            return '<span data-bs-toggle="tooltip" title="'
                . Html::encode($label) . '" class="badge text-bg-' . $class . '">'
                . Html::encode($label) . '</span>';
        },
        filter: DropdownFilter::widget()
            ->addAttributes([
                'name' => 'status',
                'class' => 'native-reset',
            ])
            ->optionsData($optionsDataStatusDropDownFilter),
        encodeContent: false,
        withSorting: true,
        visible: true,
    ),
    new DataColumn(
        'so_id',
        header: $translator->translate('salesorder.number.status'),
        content: static function (Quote $model) use ($urlGenerator, $soR): A {
            $so_id = $model->getSoId();
            $so = $soR->repoSalesOrderUnloadedquery($so_id);
            if (null !== $so) {
                $number = $so->getNumber();
                $statusId = $so->getStatusId();
                if (null !== $number && ($statusId > 0)) {
                    return   new A()
                        ->addAttributes([
                            'style' => 'text-decoration:none',
                            'class' => 'badge text-bg-'
                            . $soR->getSpecificStatusArrayClass($statusId)])
                        ->content($number
                                . ' '
                                . $soR->getSpecificStatusArrayLabel((string) $statusId))
                        ->href($urlGenerator->generate('salesorder/view',
                            ['id' => $so_id]));
                }
                if ($model->getSoId() === '0' && $model->getStatusId() === 7) {
                    if ($statusId > 0) {
                        return  new A()
                        ->addAttributes(['class' => 'btn btn-warning'])
                               ->content($soR->getSpecificStatusArrayLabel((string) $statusId))
                               ->href('');
                    }
                }
            }
            return  new A();
        },
        encodeContent: false
    ),
    new DataColumn(
        property: 'filterQuoteNumber',
        header: $translator->translate('quote.number'),
        content: static function (Quote $model) use ($urlGenerator): A {
            return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view', ['id' => $model->getId()]), ['style' => 'text-decoration:none']);
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 80px']),
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static fn (Quote $model): string => ($model->getDateCreated())->format('Y-m-d'),
        withSorting: true,
    ),
    new DataColumn(
        'date_expires',
        content: static fn (Quote $model): string => ($model->getDateExpires())->format('Y-m-d'),
        withSorting: true,
    ),
    new DataColumn(
        'date_required',
        content: static fn (Quote $model): string => ($model->getDateRequired())->format('Y-m-d'),
    ),
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static function (Quote $model): string {
            $clientName = $model->getClient()?->getClientName();
            $clientSurname = $model->getClient()?->getClientSurname();
            if (null !== $clientName && null !== $clientSurname) {
                return Html::encode($clientName . str_repeat(' ', 2) . $clientSurname);
            }
            return '';
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'name' => 'filterClient',
                    'class' => 'native-reset',
                ])
                ->optionsData($optionsDataClientsDropdownFilter),
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterQuoteAmountTotal',
        header: $translator->translate('total') . ' ➡️ ' . $s->getSetting('currency_symbol'),
        content: static function (Quote $model) use ($decimalPlaces): Label {
            $quoteTotal = $model->getQuoteAmount()->getTotal();
            return
                 new Label()
                    ->attributes(['class' => $model->getQuoteAmount()->getTotal() > 0.00 ? 'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $quoteTotal ? number_format($quoteTotal, $decimalPlaces) : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        filter: TextInputFilter::widget()
                ->addAttributes([
                    'style' => 'max-width: 50px',
                    'class' => 'native-reset',
                ]),
        withSorting: false,
        footer:  new Span()->addAttributes(['style' => 'text-align: right; display: block; width: 100%;'])->content(number_format($totalAmount, $decimalPlaces))->render(),
    ),
];

$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));

$gridSummary = $s->gridSummary(
    $sortedAndPagedPaginator,
    $translator,
    $defaultPageSizeOffsetPaginator,
    $translator->translate('quotes'),
    $label,
);

// Add left-aligned wrapper when additional
// columns are visible to accommodate more columns
$tableOrTableResponsive = $visible ? 'table-responsive' : 'table';

if ($visible) {
    echo '<div class="text-start">';
}

// Row Grouping Implementation
$previousGroupValue = '';

// Function to get group value based on selected field
$getGroupValue = static function (Quote $quote) use ($groupBy, $qR): string {
    return match ($groupBy) {
        'client' => $quote->getClient()?->getClientFullName() ?? 'Unknown Client',
        'status' => $qR->getSpecificStatusArrayLabel((string) $quote->getStatusId()),
        'month' => $quote->getDateCreated()->format('Y-m'),
        'year' => $quote->getDateCreated()->format('Y'),
        'date' => $quote->getDateCreated()->format('Y-m-d'),
        'client_group' => $quote->getClient()?->getClientGroup() ?? 'No Group',
        'amount_range' => match (true) {
            ($quote->getQuoteAmount()->getTotal() ?? 0) < 100 => '< $100',
            ($quote->getQuoteAmount()->getTotal() ?? 0) < 500 => '$100 - $500',
            ($quote->getQuoteAmount()->getTotal() ?? 0) < 1000 => '$500 - $1000',
            default => '> $1000'
        },
        default => 'No Group'
    };
};

// Calculate totals per group (only if grouping is enabled)
$groupTotals = [];
if ($enableGrouping) {
    /**
     * @var App\Invoice\Entity\Quote $quote
     */
    foreach ($sortedAndPagedPaginator->read() as $quote) {
        $groupValue = $getGroupValue($quote);
        if (!isset($groupTotals[$groupValue])) {
            $groupTotals[$groupValue] = [
                'count' => 0,
                'total' => 0.00,
            ];
        }
        $groupTotals[$groupValue]['count']++;
        $groupTotals[$groupValue]['total'] += $quote->getQuoteAmount()->getTotal() ?? 0.00;
    }
}

$gridView = GridView::widget()
// unpack the contents within the array using the three dot splat operator
->bodyRowAttributes(['class' => 'align-left'])
->tableAttributes(['class' => $tableOrTableResponsive . ' table-bordered table-striped h-75', 'id' => 'table-quote'])
->columns(...$columns)
->columnGrouping(true); // Enable HTML column grouping for better styling

$columnCount = count(array_filter($columns,
    fn(ColumnInterface $col) => $col->isVisible()
));

// Apply grouping only if enabled
if ($enableGrouping) {
    $gridView = $gridView->beforeRow(static function (array|object $quote) use (
        &$previousGroupValue,
        $getGroupValue,
        $groupTotals,
        $decimalPlaces,
        $groupBy,
        $s,
        $columnCount
    ): ?\Yiisoft\Html\Tag\Tr {
        // Ensure the quote is of the expected type
        assert($quote instanceof Quote);
        $currentGroupValue = $getGroupValue($quote);
        
        if ($previousGroupValue !== $currentGroupValue) {
            $previousGroupValue = $currentGroupValue;
            $groupData = $groupTotals[$currentGroupValue];
            $currencySymbol = $s->getSetting('currency_symbol');
            
            return \Yiisoft\Html\Html::tr()
                ->addClass('group-header bg-secondary text-white fw-bold group-collapsible')
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
                            '<span class="fs-5">' . Html::encode(ucfirst($groupBy)) . ': ' . Html::encode($currentGroupValue) . '</span>' .
                            '<span class="badge bg-primary ms-2">' . $groupData['count'] . ' quote' . ($groupData['count'] === 1 ? '' : 's') . '</span>' .
                            '</div>' .
                            '<div class="text-end">' .
                            '<small class="d-block">Total: <strong>' . number_format($groupData['total'], $decimalPlaces) . ' ' . $currencySymbol . '</strong></small>' .
                            '</div>' .
                            '</div>'
                        )
                        ->encode(false)
                );
        }
        
        return null;
    });
}

$toolbarString
    =  new Form()->post($urlGenerator->generate($quoteIndex))->csrf($csrf)->open()
    .  new Div()->addClass('float-start')->content(
         new H4()
            ->addClass('me-3 d-inline-block')
            ->content($translator->translate('quote'))
        . Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
        . $allVisible
        . $toolbarReset
        . ($clientCount == 0 ? $disabledAddQuoteButton : $enabledAddQuoteButton)
        . Html::closeTag('div')
        .  new Div()
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                 new Label()
                    ->addClass('btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $translator->translate('group.by') . ':')
                .
                 new Select()
                    ->addClass('form-select group-by-select')
                    ->addAttributes([
                        'style' => 'max-width: 150px;',
                        'data-base-url' => $urlGenerator->generate($quoteIndex),
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

echo $gridView
->dataReader($sortedAndPagedPaginator)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->footerRowAttributes(['class' => 'card-footer bg-success text-white fw-bold'])
->enableFooter(true)
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->id('w2-grid')
->summaryAttributes(['class' => 'mt-3 me-3 summary d-flex justify-content-between align-items-center'])
/**
 * Related logic: see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in quote/index
 */
->summaryTemplate('<div class="d-flex align-items-center">' . $pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'quote') . ' ' . $gridSummary . '</div>')
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

// Close the left-aligned wrapper div when additional columns are visible
if ($visible) {
    echo '</div>';
}

echo $modal_add_quote;
?>

<?php
$magnifierScript = <<<JS
// Initialize Quote Amount Magnifier when page loads
document.addEventListener('DOMContentLoaded', function() {
    class QuoteAmountMagnifier {
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

            const tableContainer = document.getElementById('table-quote')
                    || document.querySelector('.table-responsive');
            if (!tableContainer) return;
            this.observer.observe(tableContainer, {
                childList: true,
                subtree: true
            });
        }
    }

    new QuoteAmountMagnifier();

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
CSS;

echo Html::script($magnifierScript)->type('module');
echo Html::style($magnifierStyle);

if ($enableGrouping):
    $groupingScript = <<<JS
// Group collapsible functionality
window.toggleGroupRows = function(groupHeader) {
    const toggleIcon = groupHeader.querySelector('.group-toggle-icon');
    let nextRow = groupHeader.nextElementSibling;
    let isCollapsing = toggleIcon.classList.contains('bi-chevron-down');

    if (isCollapsing) {
        toggleIcon.classList.remove('bi-chevron-down');
        toggleIcon.classList.add('bi-chevron-right');
    } else {
        toggleIcon.classList.remove('bi-chevron-right');
        toggleIcon.classList.add('bi-chevron-down');
    }

    while (nextRow && !nextRow.classList.contains('group-header')) {
        if (isCollapsing) {
            nextRow.style.display = 'none';
        } else {
            nextRow.style.display = '';
        }
        nextRow = nextRow.nextElementSibling;
    }
};

window.toggleAllGroups = function(expand = null) {
    const groupHeaders = document.querySelectorAll('.group-header');
    groupHeaders.forEach(header => {
        const toggleIcon = header.querySelector('.group-toggle-icon');
        const isCurrentlyCollapsed =
                        toggleIcon.classList.contains('bi-chevron-right');

        if (expand === null) {
            window.toggleGroupRows(header);
        } else if (expand && isCurrentlyCollapsed) {
            window.toggleGroupRows(header);
        } else if (!expand && !isCurrentlyCollapsed) {
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

/* Make quote rows within groups slightly indented visually */
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
