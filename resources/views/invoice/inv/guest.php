<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H4;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
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
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsStatusDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientsDropDownFilter
 */

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

/**
 * @var ColumnInterface[] $columns
 */
$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static fn (Inv $model) => (string) $model->getId(),
        withSorting: true,
    ),
    new ActionColumn(
        header: '',
        before: Html::openTag('div', ['class' => 'dropdown'])
            . Html::openTag('button', [
                'class' => 'btn btn-info dropdown-toggle',
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
                url: static function (Inv $inv) use ($translator, $urlGenerator): string {
                    return $urlGenerator->generate('inv/pdf_dashboard_exclude_cf',
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
                url: static function (Inv $inv) use ($translator, $urlGenerator):
                string {
                    return $urlGenerator->generate('inv/pdf_dashboard_include_cf',
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
        ],
        after: Html::closeTag('div')
               . Html::closeTag('div')
               . Html::closeTag('div'),                
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
            $statusId = $model->getStatus_id();
            if ($statusId === null) {
                return '<span class="label label-default">N/A</span>';
            }
            $emoji = $iR->getSpecificStatusArrayEmoji($statusId);
            $label = $iR->getSpecificStatusArrayLabel((string) $statusId);
            
            // Add read-only indicator
            if (($model->getIs_read_only())
                                && $s->getSetting('disable_read_only') == '0') {
                $label .= ' 🚫';
            }
            // Add recurring indicator
            if ($irR->repoCount((string) $model->getId()) > 0) {
                $label .= ' ' . $translator->translate('recurring') . ' 🔄';
            }
            
            return '<span data-bs-toggle="tooltip" title="'
            . Html::encode($label) . '" class="label label-'
            . $iR->getSpecificStatusArrayClass($statusId) . '">'
            . $emoji . ' ' . $label .  '</span>';
        },
        filter: DropdownFilter::widget()
            ->addAttributes([
                'name' => 'status',
                'class' => 'native-reset',
            ])
            ->optionsData($optionsStatusDropDownFilter),
        encodeContent: false,
        withSorting: false,
        visible: true,
    ),
    new DataColumn(
        property: 'filterInvNumber',
        header: $translator->translate('number'),
        content: static function (Inv $model) use ($urlGenerator): A {
            return  A::tag()
                    ->addAttributes(['style' => 'text-decoration:none'])
                    ->content(($model->getNumber() ?? '#') . ' 🔍')
                    ->href($urlGenerator->generate(
                                        'inv/view', ['id' => $model->getId()]));
        },
        encodeContent: false,
        filter: $optionsInvNumberDropDownFilter,
        withSorting: false,
    ),
    // Credit note for the invoice
    new DataColumn(
        header: Label::tag()->content('💳')->addAttributes(
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('credit.invoice.for.invoice')
            ])->render(),
        encodeHeader: false,    
        property: 'creditinvoice_parent_id',
        content: static function (Inv $model) use ($urlGenerator, $iR): A {
            $visible = $iR->repoInvUnLoadedquery(
                                        $model->getCreditinvoice_parent_id());
            if (null !== $visible) {
                $url = ($visible->getNumber() ?? '#') . '💳';
                return  A::tag()
                        ->addAttributes(['style' => 'text-decoration:none'])
                        ->content($url)
                        ->href($urlGenerator->generate('inv/view',
                                ['id' => $model->getCreditinvoice_parent_id()]));
            }
            return A::tag()->content('')->href('');
        },
        encodeContent: false,
        withSorting: false,
        visible: true,
    ),
    /**
     * Related logic: see https://github.com/rossaddison/yii-dataview/commit/9e908d87cddd0661b440cb989429e1652e00a9fe
     */
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static fn (Inv $model):
            string => Html::encode($model->getClient()?->getClient_full_name()),
        encodeContent: false,
        filter: DropdownFilter::widget()
                ->addAttributes([
                    'name' => 'client_id',
                    'class' => 'native-reset',
                ])
                ->optionsData($optionsClientsDropDownFilter),
        withSorting: false,
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static fn (Inv $model):
            string => (!is_string($dateCreated = $model->getDate_created()) ?
                $dateCreated->format('Y-m-d') : ''),
        withSorting: false,
    ),
    new DataColumn(
        'date_due',
        header: $translator->translate('due.date'),
        content: static function (Inv $model) use ($dateHelper):
                                                    Yiisoft\Html\Tag\CustomTag {
            $now = new \DateTimeImmutable('now');
            return Html::tag('label')
                    ->attributes([
                        'class' => $model->getDate_due() > $now ?
                            'label label-success' : 'label label-warning'])
                    ->content(!is_string($dateDue = $model->getDate_due()) ?
                            $dateDue->format('Y-m-d') : '');
        },
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'filterInvAmountTotal',
        header: $translator->translate('total')
            . ' ( ' . $s->getSetting('currency_symbol')
            . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountTotal = $model->getInvAmount()->getTotal();
            return
                Label::tag()
                    ->attributes(['class' => $invAmountTotal > 0.00 ?
                            'label label-success' : 'label label-warning'])
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
        header: $translator->translate('paid')
            . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountPaid = $model->getInvAmount()->getPaid();
            return Label::tag()
                    ->attributes([
                        'class' => $model->getInvAmount()->getPaid()
                            < $model->getInvAmount()->getTotal() ?
                            'label label-danger' : 'label label-success'])
                    ->content(Html::encode(null !== $invAmountPaid
                            ? number_format($invAmountPaid > 0.00 ?
                                    $invAmountPaid : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        withSorting: false,
    ),
    new DataColumn(
        'id',
        header: $translator->translate('balance')
            . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Inv $model) use ($decimalPlaces): Label {
            $invAmountBalance = $model->getInvAmount()->getBalance();
            return  Label::tag()
                    ->attributes(['class' => $invAmountBalance > 0.00 ?
                            'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $invAmountBalance
                            ? number_format($invAmountBalance > 0.00 ?
                                    $invAmountBalance : 0.00, $decimalPlaces)
                            : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        withSorting: false,
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


$toolbarString = Form::tag()->post(
                $urlGenerator->generate('inv/guest'))->csrf($csrf)->open()
        . Div::tag()->addClass('float-start m-3')->content(
                H4::tag()
                    ->addClass('me-3 d-inline-block')
                    ->content($translator->translate('invoice')
                ) 
            .   $toolbarReset
            .   Button::ascDesc(
                $urlGenerator, 'client_id', 'warning',
                $translator->translate('client'), true)
                )->encode(false)->render()
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
    ->tableAttributes([
        'class' => 'table table-striped text-center h-75',
        'id' => 'table-invoice-guest'])
    ->columns(...$columns)
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
    ->summaryTemplate(($viewInv
                       ? $pageSizeLimiter::buttonsGuest(
                                 $userInv, $urlGenerator, $translator, 'inv',                                                       $defaultPageSizeOffsetPaginator) : '') . ' '
                       . $grid_summary)
    ->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
    ->noResultsText($translator->translate('no.records'))
    ->toolbar($toolbarString);
?>
<div id="angular-amount-magnifier-app">
    <app-root></app-root>
</div>

<script>
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

            element.style.transition = `all ${this.animationDuration}ms ease-in-out`;
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
            
            element.style.fontSize = `${newFontSize}px`;
            element.style.fontWeight = 'bold';
            element.style.backgroundColor = bgColor;
            element.style.border = `2px solid ${borderColor}`;
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

            const tableContainer = document.querySelector('.table-responsive')
                    || document.body;
            this.observer.observe(tableContainer, {
                childList: true,
                subtree: true
            });
        }
    }

    // Initialize the amount magnifier
    new InvoiceAmountMagnifier();
    
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
});
</script>

<style>
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
</style>