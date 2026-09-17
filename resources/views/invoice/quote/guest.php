<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\Quote\Quote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Infrastructure\Persistence\Quote\Quote $quote
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\QuoteAmount\QuoteAmountRepository $qaR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var int $defaultPageSizeOffsetPaginator
 * @var array $quoteStatuses
 * @var array $quoteStatuses[$status]
 * @var bool $editInv
 * @var string $alert
 * @var string $csrf
 * @var string $status
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';
$quoteGuest = 'quote/guest';
$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? $quoteGuest))
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

// WCAG 1.4.1: btn-primary vs btn-secondary was the only cue for which
// status tab is currently selected -- aria-current="page" now carries
// that too, the standard ARIA way to mark the active item in a set of
// navigation-style links (matches this project's own use of the same
// attribute elsewhere for "current" states).
$statusTab = static fn (string $label, int $statusValue): string => Html::a(
    $translator->translate($label),
    $urlGenerator->generate($quoteGuest, ['page' => 1, 'status' => $statusValue]),
    array_filter([
        'class' => 'btn ' . ($status == $statusValue ? 'btn-primary' : 'btn-secondary'),
        'aria-current' => $status == $statusValue ? 'page' : null,
    ]),
)->render();

$statusBar =   new Div()
    ->addClass('btn-group index-options')
    ->content(
        $statusTab('all', 0)
        . $statusTab('sent', 2)
        . $statusTab('viewed', 3)
        . $statusTab('approved', 4)
        . $statusTab('rejected', 5)
        . $statusTab('canceled', 6),
    )
    ->encode(false)
    ->render();

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (Quote $model): string {
            return (string) $model->reqId();
        },
        withSorting: true,
    ),
    new DataColumn(
        'status_id',
        header: $translator->translate('status'),
        content: static function (Quote $model) use ($qR): Yiisoft\Html\Tag\CustomTag|string {
            $span = $qR->getSpecificStatusArrayLabel((string) $model->reqStatusId());
                $class = $qR->getSpecificStatusArrayClass((string) $model->reqStatusId());
                // Dropped 'id' => '#quote-guest': a malformed, dead id
                // (never referenced by any JS/CSS) duplicated onto every
                // single row's span -- a real HTML-validity/duplicate-ID
                // issue serving no purpose.
                return Html::tag('span', $span, ['class' => 'badge text-bg-' . $class]);
        },
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        property: 'filterQuoteNumber',
        header: $translator->translate('quote.number'),
        content: static function (Quote $model) use ($urlGenerator): A {
            return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view', ['id' => $model->reqId()]), ['class' => 'text-decoration-none']);
        },
        encodeContent: false,
        // WCAG 1.3.1/3.3.2: no aria-label/placeholder at all.
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes([
                    'style' => 'max-width: 80px',
                    'aria-label' => $translator->translate('filter.by') . ' '
                        . $translator->translate('quote.number'),
                    'title' => $translator->translate('quote.number'),
                    'placeholder' => $translator->translate('quote.number'),
                ]),
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('id'),
        content: static fn (Quote $model): string => Html::encode($model->getClient()?->getClientName() ?? ''),
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
        'id',
        header: $translator->translate('total'),
        content: static function (Quote $model) use ($s, $qaR): string {
            $quote_id = $model->reqId();
            $quote_amount = (($qaR->repoQuoteAmountCount($quote_id) > 0) ? $qaR->repoQuotequery($quote_id) : null);
            return $s->formatCurrency(null !== $quote_amount ? $quote_amount->getTotal() : 0.00);
        },
    ),
];

$gridSummary = $s->gridSummary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('quotes'),
    '',
);

$toolbarString
    =  new Form()->post($urlGenerator->generate('quote/guest'))->csrf($csrf)->open()
    . $statusBar
    .  new Div()->addClass('float-end m-3')->content(
            $toolbarReset . $autoFitColumns . $resetColumnWidths
       )->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75 resizable-grid','id' => 'table-quote-guest'])
->columnGrouping(true)
->dataReader($paginator)
->columns(...$columns)
->urlCreator($urlCreator)
// yii-dataview 1.3's accessibility() opt-in (added scope="col"/aria-sort
// on header cells and aria-current/aria-disabled/aria-label/role="link"
// on pagination links -- disabled by default, so none of this rendered
// before enabling it here).
->accessibility(true)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
// aria-hidden: purely decorative -- accessibility() above adds the real
// aria-sort attribute on each sortable <th>, so these glyphs would
// otherwise be redundant, inconsistently-read Unicode noise on top of a
// state a screen reader already announces correctly.
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50" aria-hidden="true">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold" aria-hidden="true">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold" aria-hidden="true">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('quote'))
->emptyCell($translator->translate('not.set'))
// WCAG 1.4.3: plain CSS `red` (#FF0000) on white is ~4.0:1, below AA's
// 4.5:1 minimum. Bootstrap's own text-danger (#dc3545, ~4.5:1) matches
// every other "attention" color already used in this grid.
->emptyCellAttributes(['class' => 'text-danger'])
->id('w7-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
