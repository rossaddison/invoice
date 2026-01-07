<?php

declare(strict_types=1);

use App\Invoice\Entity\SalesOrder;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Select;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;
use Yiisoft\Yii\DataView\Filter\Widget\DropdownFilter;

/**
 * @var App\Invoice\Entity\SalesOrder $so
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\SalesOrderAmount\SalesOrderAmountRepository $soaR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $salesorders
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $status
 * @var array $so_statuses
 * @var array $so_statuses[$status]
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $groupBy
 * @var string $sortString
 * @var bool $visible
 * @psalm-var positive-int $page
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo H6::tag()->content($translator->translate('salesorder'))->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-primary me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'salesorder/index'))
    ->id('btn-reset')
    ->render();

$allVisible = A::tag()
    ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('hide.or.unhide.columns')])
    ->addClass('btn btn-warning me-1 ajax-loader')
    ->content('↔️')
    ->href($urlGenerator->generate('setting/visible', ['origin' => 'salesorder']))
    ->id('btn-all-visible')
    ->render();

$enableGrouping = $groupBy !== 'none';

$sort = Sort::only(['id','status_id','number','date_created','client_id'])
    ->withOrderString($sortString);

$paginator = (new OffsetPaginator($salesorders))
    ->withPageSize(
        $defaultPageSizeOffsetPaginator > 0 ?
            $defaultPageSizeOffsetPaginator : 1
    )
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

// see SalesOrder/SalesOrderRepository getStatuses function
// && Invoice\Asset\invoice\css\style.css & yii3i.css

$statusBar
    = Div::tag()
        ->addClass('btn-group index-options')
        ->content(
            Html::a(
                $translator->translate('all'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 0]),
                [
                    'class' => 'btn ' . ($status == 0 ?
                        'btn-primary' : 'btn-default'),
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('1'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 1]),
                [
                    'class' => 'btn ' . ($status == 1 ? 'btn-primary' : 'label '
                    . $soR->getSpecificStatusArrayClass(1)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('2'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 2]),
                [
                    'class' => 'btn ' . ($status == 2 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(2)),
                    'style' => 'text-decoration:none',
                    'data-bs-toggle' => 'tooltip',
                    'title' => $s->getSetting('debug_mode') === '1'
                        ? $translator->translate(
                      'payment.term.add.additional.terms.at.setting.repository')
                        : '',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('3'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 3]),
                [
                    'class' => 'btn ' . ($status == 3 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(3)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('4'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 4]),
                [
                    'class' => 'btn ' . ($status == 4 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(4)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('5'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 5]),
                [
                    'class' => 'btn ' . ($status == 5 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(5)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('6'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 6]),
                [
                    'class' => 'btn ' . ($status == 6 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(6)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('7'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 7]),
                [
                    'class' => 'btn ' . ($status == 7 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(7)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('8'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 8]),
                [
                    'class' => 'btn ' . ($status == 8 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(8)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('9'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 9]),
                [
                    'class' => 'btn ' . ($status == 9 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(9)),
                    'style' => 'text-decoration:none',
                ],
            )
            . Html::a(
                $soR->getSpecificStatusArrayLabel('10'),
                $urlGenerator->generate('salesorder/index',
                        ['page' => 1, 'status' => 10]),
                [
                    'class' => 'btn ' . ($status == 10 ? 'btn-primary' :
                    'label ' . $soR->getSpecificStatusArrayClass(10)),
                    'style' => 'text-decoration:none',
                ],
            ),
        )
        ->encode(false)
        ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (SalesOrder $model): string {
            return (string) $model->getId();
        },
        visible: $visible,
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (SalesOrder $model) use ($urlGenerator): A {
            return Html::a(Html::tag('i', '',
                    ['class' => 'fa fa-eye fa-margin']),
                    $urlGenerator->generate('salesorder/view',
                            ['id' => $model->getId()]), []);
        },
    ),
    new DataColumn(
        'status_id',
        header: $translator->translate('status'),
        content: static function (SalesOrder $model) use ($soR, $urlGenerator):
            Yiisoft\Html\Tag\CustomTag {
            $statusId = $model->getStatus_id();
            if (null !== $statusId) {
                $span = $soR->getSpecificStatusArrayLabel((string) $statusId);
                $class = $soR->getSpecificStatusArrayClass($statusId);
                $spanTag = Html::tag('span', $span, ['id' => '#so-to-invoice',
                    'class' => 'label ' . $class]);
                if (7 !== $statusId) {
                    return $spanTag;
                } else {
                    return Html::tag('a', $spanTag, [
                        'href' => $urlGenerator->generate(
                                'salesorder/so_to_invoice',
                                ['id' => $model->getId()]), 
                        'style' => 'text-decoration:none']);
                }   
            }
            return Html::tag('span');
        },
    ),
    new DataColumn(
        'number',
        content: static function (SalesOrder $model) use ($urlGenerator): A {
            return Html::a($model->getNumber() ?? '#',
                $urlGenerator->generate('salesorder/view',
                        ['id' => $model->getId()]),
                                            ['style' => 'text-decoration:none']);
        },
    ),
    new DataColumn(
        'quote_id',
        header: $translator->translate('quote'),
        content: static function (SalesOrder $model) use ($urlGenerator):
            string|A {
            $quote = $model->getQuote();
            return ($quote
            ? Html::a($quote->getNumber() ?? '#',
                    $urlGenerator->generate('quote/view',
                            ['id' => $quote->getId()]),
                                    ['style' => 'text-decoration:none']) : '');
        },
        visible: $visible,
    ),
    new DataColumn(
        'inv_id',
        header: $translator->translate('invoice'),
        content: static function (SalesOrder $model) use ($urlGenerator, $iR):
            string|A {
            $invId = $model->getInv_id();
            if ($invId !== null && $invId !== '' && $invId !== '0') {
                $inv = $iR->repoInvUnloadedquery($invId);
                return ($inv
                ? Html::a($inv->getNumber() ?? '#', $urlGenerator->generate(
                        'inv/view', ['id' => $invId]),
                                ['style' => 'text-decoration:none']) : '');
            }
            return '';
        },
        visible: $visible,
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static function (SalesOrder $model) use ($dateHelper): string {
            /**
             * @psalm-suppress PossiblyInvalidMethodCall $model->getDate_created()->format('Y-m-d')
             */
            return $model->getDate_created() instanceof \DateTimeImmutable
                    ? $model->getDate_created()->format('Y-m-d')
                    : '';
        },
        encodeContent: true,
        visible: $visible,
    ),
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static function (SalesOrder $model): string {
            return Html::encode($model->getClient()?->getClient_full_name() ?? '');
        },
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'name' => 'client_id',
                    'class' => 'native-reset',
                ])
                ->optionsData($optionsDataClientsDropdownFilter),
        withSorting: false,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('total'),
        content: function (SalesOrder $model) use ($s, $soaR): string {
            $so_id = $model->getId();
            $so_amount = (($soaR->repoSalesOrderAmountCount(
                (string) $so_id) > 0) ? $soaR->repoSalesOrderquery(
                        (string) $so_id) : null);
            return $s->format_currency(null !== $so_amount ?
                                                $so_amount->getTotal() : 0.00);
        },
        visible: $visible,
    ),
];

$grid_summary =  $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('salesorders'),
    (string) $so_statuses[$status]['label'],
);

// Row Grouping Implementation
$previousGroupValue = '';

// Function to get group value based on selected field
$getGroupValue = static function (SalesOrder $salesorder) use ($groupBy, $soR):
        string {
    return match ($groupBy) {
        'client' => $salesorder->getClient()?->getClient_full_name()
                ?? 'Unknown Client',
        'status' => $soR->getSpecificStatusArrayLabel(
                                            (string) $salesorder->getStatus_id()),
        'month' => $salesorder->getDate_created()->format('Y-m'),
        'year' => $salesorder->getDate_created()->format('Y'),
        'date' => $salesorder->getDate_created()->format('Y-m-d'),
        default => 'No Group'
    };
};

// Calculate totals per group (only if grouping is enabled)
$groupTotals = [];
if ($enableGrouping) {
    /**
     * @var SalesOrder $salesorder
     */
    foreach ($salesorders as $salesorder) {
        $groupValue = $getGroupValue($salesorder);
        if (!isset($groupTotals[$groupValue])) {
            $groupTotals[$groupValue] = [
                'count' => 0,
                'total' => 0.00,
            ];
        }
        $groupTotals[$groupValue]['count']++;
        $so_id = $salesorder->getId();
        $so_amount = (($soaR->repoSalesOrderAmountCount((string) $so_id) > 0) ?
                            $soaR->repoSalesOrderquery((string) $so_id) : null);
        $groupTotals[$groupValue]['total'] += null !== $so_amount ?
                                        ($so_amount->getTotal() ?? 0.00) : 0.00;
    }
}

$toolbarString = Form::tag()->post($urlGenerator->generate(
                                    'salesorder/index'))->csrf($csrf)->open()
    . Div::tag()->addClass('float-start')->content(
        Html::openTag('div', ['class' => 'btn-group me-2', 'role' => 'group'])
        . $allVisible
        . $toolbarReset
        . Html::closeTag('div')
        . $statusBar
        . Div::tag()
            ->addClass('btn-group ms-3')
            ->addAttributes(['role' => 'group'])
            ->content(
                Label::tag()
                    ->addClass(
                       'btn btn-outline-secondary active bi bi-collection me-1')
                    ->content(' ' . $translator->translate('group.by') . ':')
                .
                Select::tag()
                    ->addClass('form-select')
                    ->addAttributes([
                        'style' => 'max-width: 150px;',
                        'onchange' => 'window.location.href=\''
                            . $urlGenerator->generate('salesorder/index')
                            . '?groupBy=\' + this.value'
                    ])
                    ->optionsData([
                        'none' => $translator->translate('grouping.none'),
                        'status' => $translator->translate('status'),
                        'client' => $translator->translate('client'),
                        'date' => $translator->translate('date'),
                        'month' => $translator->translate('month'),
                        'year' => $translator->translate('year'),
                    ])
                    ->value($groupBy)
            )
            ->encode(false)
            ->render()
        . ($enableGrouping ? 
            Div::tag()
                ->addClass('btn-group ms-2')
                ->addAttributes(['role' => 'group'])
                ->content(
                    HtmlButton::tag()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(false)',
                            'title' => 'Collapse All Groups'
                        ])
                        ->content(I::tag()->addClass('bi bi-chevron-up')) .
                    HtmlButton::tag()
                        ->type('button')
                        ->addClass('btn btn-outline-secondary btn-sm')
                        ->addAttributes([
                            'onclick' => 'toggleAllGroups(true)',
                            'title' => 'Expand All Groups'
                        ])
                        ->content(I::tag()->addClass('bi bi-chevron-down'))
                )
                ->encode(false)
                ->render() : ''
        )
    )->encode(false)->render()
    . Div::tag()->addClass('clearfix')->content('')->render()
    . Form::tag()->close();

$gridView = GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75',
                   'id' => 'table-salesorder'])
->columns(...$columns)
->columnGrouping(true); // Enable HTML column grouping for better styling

// Apply grouping only if enabled
if ($enableGrouping) {
    $gridView = $gridView->beforeRow(
            static function (array|object $salesorder, $key, int $index) use (
        &$previousGroupValue,
        $getGroupValue,
        $groupTotals,
        $groupBy,
        $s
    ): ?\Yiisoft\Html\Tag\Tr {
        // Ensure the salesorder is of the expected type
        assert($salesorder instanceof SalesOrder);
        $currentGroupValue = $getGroupValue($salesorder);
        
        if ($previousGroupValue !== $currentGroupValue) {
            $previousGroupValue = $currentGroupValue;
            $groupData = $groupTotals[$currentGroupValue];
            $currencySymbol = $s->getSetting('currency_symbol');
            
            // Get column count
            $columnCount = 9;
            
            return \Yiisoft\Html\Html::tr()
                ->addClass(
                'group-header bg-secondary text-white fw-bold group-collapsible')
                ->addAttributes(['onclick' => 'toggleGroupRows(this)'])
                ->cells(
                    \Yiisoft\Html\Html::td()
                        ->addAttributes(['colspan' => (string) $columnCount])
                        ->addClass('p-3')
                        ->content(
                            '<div class="d-flex justify-content-between'
                                . ' align-items-center">' .
                            '<div>' .
                            '<i class="bi bi-chevron-down me-2'
                                . ' group-toggle-icon"></i>' .
                            '<i class="bi bi-folder2-open me-2"></i>' .
                            '<span class="fs-5">'
                                . Html::encode(ucfirst($groupBy))
                                . ': ' . Html::encode($currentGroupValue)
                                . '</span>' .
                            '<span class="badge bg-primary ms-2">'
                                . $groupData['count'] . ' order'
                                . ($groupData['count'] === 1 ? '' : 's')
                                . '</span>' .
                            '</div>' .
                            '<div class="text-end">' .
                            '<small class="d-block">Total: <strong>'
                                . number_format($groupData['total'], 2)
                                . ' ' . $currencySymbol . '</strong></small>' .
                            '</div>' .
                            '</div>'
                        )
                        ->encode(false)
                );
        }
        
        return null;
    });
}

echo $gridView
->dataReader($paginator)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->id('w12-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($pageSizeLimiter::buttons(
        $currentRoute, $s, $translator, $urlGenerator, 'salesorder')
        . ' ' . $grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

?>

<?php if ($enableGrouping): 
    $groupingScript = <<<JS
// Toggle individual group rows
function toggleGroupRows(headerRow) {
    const icon = headerRow.querySelector('.group-toggle-icon');
    let nextRow = headerRow.nextElementSibling;
    let isCollapsed = icon.classList.contains('bi-chevron-right');
    
    // Toggle icon
    if (isCollapsed) {
        icon.classList.remove('bi-chevron-right');
        icon.classList.add('bi-chevron-down');
    } else {
        icon.classList.remove('bi-chevron-down');
        icon.classList.add('bi-chevron-right');
    }
    
    // Toggle rows until next group header or end of table
    while (nextRow && !nextRow.classList.contains('group-header')) {
        if (isCollapsed) {
            nextRow.style.display = '';
        } else {
            nextRow.style.display = 'none';
        }
        nextRow = nextRow.nextElementSibling;
    }
}

// Toggle all groups
function toggleAllGroups(expand) {
    const groupHeaders = document.querySelectorAll('.group-header');
    groupHeaders.forEach(header => {
        const icon = header.querySelector('.group-toggle-icon');
        let nextRow = header.nextElementSibling;
        
        // Set icon state
        if (expand) {
            icon.classList.remove('bi-chevron-right');
            icon.classList.add('bi-chevron-down');
        } else {
            icon.classList.remove('bi-chevron-down');
            icon.classList.add('bi-chevron-right');
        }
        
        // Toggle rows
        while (nextRow && !nextRow.classList.contains('group-header')) {
            nextRow.style.display = expand ? '' : 'none';
            nextRow = nextRow.nextElementSibling;
        }
    });
}

// Add cursor pointer to group headers
document.addEventListener('DOMContentLoaded', function() {
    const groupHeaders = document.querySelectorAll('.group-header');
    groupHeaders.forEach(header => {
        header.style.cursor = 'pointer';
    });
});
JS;

    $groupingStyle = <<<CSS
.group-collapsible:hover {
    background-color: #495057 !important;
    cursor: pointer;
}

.group-toggle-icon {
    transition: transform 0.2s ease;
}
CSS;

    echo Html::script($groupingScript)->type('module');
    echo Html::style($groupingStyle);
endif; ?>
