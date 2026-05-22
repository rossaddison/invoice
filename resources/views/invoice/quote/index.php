<?php

declare(strict_types=1);

use App\Invoice\Quote\QuoteRepository as QR;
use App\Invoice\Quote\Widget\QuotesListWidget;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\Setting\SettingRepository as SR;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @var SR $s
 * @var QR $qR
 * @var SOR $soR
 * @var OffsetPaginator $paginator
 * @var UrlGenerator $urlGenerator
 * @var TranslatorInterface $translator
 * @var bool $visible
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var string $alert
 * @var string $csrf
 * @var string|null $defaultQuoteGroup
 * @var string|null $groupBy
 * @var string $gridSummary
 * @var string $modal_add_quote
 * @var string $sortString
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatusDropDownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

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

echo QuotesListWidget::widget()
    ->withPaginator($paginator)
    ->withQR($qR)
    ->withSoR($soR)
    ->withSR($s)
    ->withCsrf($csrf)
    ->withDecimalPlaces($decimalPlaces)
    ->withVisible($visible)
    ->withGroupBy($groupBy ?? 'none')
    ->withClientCount($clientCount)
    ->withGridSummary($gridSummary)
    ->withSortString($sortString)
    ->withOptionsDataClientsDropdownFilter($optionsDataClientsDropdownFilter)
    ->withOptionsDataStatusDropDownFilter($optionsDataStatusDropDownFilter)
    ->render();

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

if (($groupBy ?? 'none') !== 'none'):
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
