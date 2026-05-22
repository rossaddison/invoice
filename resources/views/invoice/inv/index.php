<?php

declare(strict_types=1);

use App\Invoice\Inv\Widget\InvsListWidget;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\DeliveryLocation\DeliveryLocationRepository $dlR
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\InvSentLog\InvSentLogRepository $islR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $visible
 * @var bool $visibleToggleInvSentLogColumn
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var string $alert
 * @var string $csrf
 * @var string|null $defaultInvoiceGroup
 * @var string|null $defaultInvoicePaymentMethod
 * @var string $gridSummary
 * @var string|null $groupBy
 * @var string $label
 * @var string $modal_add_inv
 * @var string $modal_copy_inv_multiple
 * @var string $modal_create_recurring_multiple
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsCreditInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsFamilyNameDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientsDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsYearMonthDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsStatusDropDownFilter
 */

$enableGrouping = isset($groupBy) && $groupBy !== 'none';
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

echo InvsListWidget::widget()
    ->withPaginator($paginator)
    ->withIR($iR)
    ->withIrR($irR)
    ->withIslR($islR)
    ->withQR($qR)
    ->withSoR($soR)
    ->withDlR($dlR)
    ->withSR($s)
    ->withCsrf($csrf)
    ->withDecimalPlaces($decimalPlaces)
    ->withVisible($visible)
    ->withVisibleInvSentLogColumn($visibleToggleInvSentLogColumn)
    ->withGroupBy($groupBy ?? 'none')
    ->withClientCount($clientCount)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->withLabel($label)
    ->withOptionsInvNumberDropDownFilter($optionsInvNumberDropDownFilter)
    ->withOptionsCreditInvNumberDropDownFilter($optionsCreditInvNumberDropDownFilter)
    ->withOptionsFamilyNameDropDownFilter($optionsFamilyNameDropDownFilter)
    ->withOptionsClientsDropDownFilter($optionsClientsDropDownFilter)
    ->withOptionsClientGroupDropDownFilter($optionsClientGroupDropDownFilter)
    ->withOptionsYearMonthDropDownFilter($optionsYearMonthDropDownFilter)
    ->withOptionsStatusDropDownFilter($optionsStatusDropDownFilter)
    ->render();

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
                '.badge.bg-success',
                '.badge.bg-warning',
                '.badge.bg-danger'
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

            if (element.classList.contains('bg-success')) {
                borderColor = '#28a745';
                bgColor = '#d4edda';
            } else if (element.classList.contains('bg-warning')) {
                borderColor = '#ffc107';
                bgColor = '#fff3cd';
            } else if (element.classList.contains('bg-danger')) {
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
// Mobile Preview Toggle
// Opens the current page in a 390 px popup window — a real browser viewport at phone
// width so Bootstrap breakpoints trigger correctly. No iframe embedding required.
// Click ‹ to collapse the button to a left-margin tab; click the tab to restore.
class MobilePreviewToggle {
    constructor() {
        this.isActive = false;
        this.previewWin = null;
        this.sideTab = null;
        this.injectStyles();
        this.createButton();
        this.createSideTab();
        // Detect if the preview window was closed externally
        setInterval(() => {
            if (this.isActive && this.previewWin && this.previewWin.closed) {
                this.isActive = false;
                this.toggleBtn.querySelector('span').textContent = '📱 Mobile Preview';
                this.toggleBtn.classList.remove('mp-on');
            }
        }, 800);
    }

    injectStyles() {
        if (document.getElementById('mp-styles')) return;
        const s = document.createElement('style');
        s.id = 'mp-styles';
        s.textContent =
            '.mp-btn{position:fixed;bottom:72px;right:20px;z-index:10001;' +
            'display:flex;align-items:center;gap:6px;' +
            'padding:9px 14px 9px 18px;background:#212529;color:#fff;' +
            'border:2px solid #495057;border-radius:22px;cursor:pointer;' +
            'font-size:13px;font-weight:600;' +
            'box-shadow:0 4px 14px rgba(0,0,0,.35);' +
            'transition:background .2s,transform .15s;}' +
            '.mp-btn:hover{background:#495057;transform:translateY(-2px);}' +
            '.mp-btn.mp-on{background:#0d6efd;border-color:#0d6efd;}' +
            '.mp-dismiss{display:inline-flex;align-items:center;justify-content:center;' +
            'width:20px;height:20px;margin-left:2px;' +
            'background:rgba(255,255,255,.15);border:none;border-radius:50%;' +
            'color:#fff;font-size:14px;line-height:1;cursor:pointer;' +
            'flex-shrink:0;padding:0;transition:background .15s;}' +
            '.mp-dismiss:hover{background:rgba(255,255,255,.35);}' +
            '.mp-side-tab{position:fixed;top:50%;left:0;z-index:10001;' +
            'transform:translateY(-50%);' +
            'width:28px;height:28px;padding:0;' +
            'background:#212529;color:#fff;' +
            'border:2px solid #495057;border-left:none;' +
            'border-radius:0 8px 8px 0;cursor:pointer;' +
            'font-size:15px;line-height:28px;text-align:center;' +
            'box-shadow:3px 0 10px rgba(0,0,0,.3);' +
            'transition:background .2s;display:none;}' +
            '.mp-side-tab:hover{background:#495057;}' +
            '.mp-side-tab.mp-visible{display:block;}';
        document.head.appendChild(s);
    }

    createButton() {
        this.toggleBtn = document.createElement('button');
        this.toggleBtn.className = 'mp-btn';
        this.toggleBtn.title = 'Preview at Android 390 px width';

        const label = document.createElement('span');
        label.textContent = '📱 Mobile Preview';
        this.toggleBtn.appendChild(label);

        const dismiss = document.createElement('button');
        dismiss.className = 'mp-dismiss';
        dismiss.title = 'Collapse to left margin';
        dismiss.textContent = '‹';
        dismiss.addEventListener('click', (e) => { e.stopPropagation(); this.collapse(); });
        this.toggleBtn.appendChild(dismiss);

        this.toggleBtn.addEventListener('click', () => this.toggle());
        document.body.appendChild(this.toggleBtn);
    }

    createSideTab() {
        this.sideTab = document.createElement('button');
        this.sideTab.className = 'mp-side-tab';
        this.sideTab.title = 'Restore Mobile Preview button';
        this.sideTab.textContent = '📱';
        this.sideTab.addEventListener('click', () => this.restore());
        document.body.appendChild(this.sideTab);
    }

    collapse() {
        if (this.isActive) this.deactivate();
        this.toggleBtn.style.display = 'none';
        this.sideTab.classList.add('mp-visible');
    }

    restore() {
        this.sideTab.classList.remove('mp-visible');
        this.toggleBtn.style.display = '';
    }

    activate() {
        this.isActive = true;
        const features = 'width=390,height=844,resizable=yes,scrollbars=yes,location=no,menubar=no,toolbar=no,status=no';
        this.previewWin = window.open(window.location.href, 'mp-preview', features);
        this.toggleBtn.querySelector('span').textContent = '🖥️ Close Preview';
        this.toggleBtn.classList.add('mp-on');
    }

    deactivate() {
        this.isActive = false;
        if (this.previewWin && !this.previewWin.closed) {
            this.previewWin.close();
        }
        this.previewWin = null;
        this.toggleBtn.querySelector('span').textContent = '📱 Mobile Preview';
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
