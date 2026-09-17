<?php

declare(strict_types=1);

use App\Infrastructure\Persistence\SalesOrder\SalesOrder;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button as HtmlButton;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

/**
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\SalesOrderAmount\SalesOrderAmountRepository $soaR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var App\Widget\GridComponents $gridComponents
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $status
 * @var array $so_statuses
 * @var array $so_statuses[$status]
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 */

$toolbarReset =  new A()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content( new I()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'salesorder/guest'))
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

// see SalesOrder/SalesOrderRepository getStatuses function
// && Invoice\Asset\invoice\css\style.css & yii3i.css

// WCAG 1.4.1: btn-primary vs each tab's own inactive color was the only
// cue for which status tab is currently selected -- aria-current="page"
// now carries that too, added without touching the existing (deliberate
// -- each tab keeps hinting at its own status color when inactive)
// class logic.
$soStatusTab = static fn (string $label, int $statusValue, string $inactiveClass, array $extraAttrs = []): string =>
    Html::a(
        $label,
        $urlGenerator->generate('salesorder/guest', ['page' => 1, 'status' => $statusValue]),
        array_filter([
            'class' => 'btn ' . ($status == $statusValue ? 'btn-primary' : $inactiveClass),
            'aria-current' => $status == $statusValue ? 'page' : null,
            ...$extraAttrs,
        ]),
    )->render();

$statusBar =   new Div()
    ->addClass('btn-group index-options')
    ->content(
        $soStatusTab($translator->translate('all'), 0, 'btn-secondary')
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('2'), 2,
            'label ' . $soR->getSpecificStatusArrayClass(2),
            [
                'data-bs-toggle' => 'tooltip',
                'title' => $s->getSetting('debug_mode') === '1'
                    ? $translator->translate(
                      'payment.term.add.additional.terms.at.setting.repository')
                    : '',
            ],
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('3'), 3,
            'label ' . $soR->getSpecificStatusArrayClass(3),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('4'), 4,
            'label ' . $soR->getSpecificStatusArrayClass(4),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('5'), 5,
            'label ' . $soR->getSpecificStatusArrayClass(5),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('6'), 6,
            'label ' . $soR->getSpecificStatusArrayClass(6),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('7'), 7,
            'label ' . $soR->getSpecificStatusArrayClass(7),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('8'), 8,
            'label ' . $soR->getSpecificStatusArrayClass(8),
        )
        . $soStatusTab(
            $soR->getSpecificStatusArrayLabel('9'), 9,
            'label ' . $soR->getSpecificStatusArrayClass(9),
        ),
    )
    ->encode(false)
    ->render();

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$columns = [
    new DataColumn(
        'id',
        header: $translator->translate('id'),
        content: static function (SalesOrder $model): string {
            return (string) $model->reqId();
        },
    ),
    new DataColumn(
        'status_id',
        header: $translator->translate('status'),
        content: static function (SalesOrder $model) use ($soR):
        Yiisoft\Html\Tag\CustomTag {
            if (null !== $model->getStatusId()) {
                $span = $soR->getSpecificStatusArrayLabel(
                        (string) $model->getStatusId());
                $class = $soR->getSpecificStatusArrayClass(
                        (int) $model->getStatusId());
                // Dropped 'id' => '#so-to-invoice': a dead, malformed id
                // (unrelated to modal_so_to_invoice.php's own real
                // id="so-to-invoice" on a different page) duplicated onto
                // every single row's span here -- same copy-paste mistake
                // as quote/guest.php's own '#quote-guest'.
                return Html::tag('span', $span, [
                    'class' => 'badge text-bg-' . $class]);
            }
            return Html::tag('span');
        },
        encodeContent: false,
    ),
    new DataColumn(
        'quote_id',
        header: $translator->translate('quote.number'),
        content: static function (SalesOrder $model) use ($urlGenerator): A {
            return Html::a($model->getQuote()?->getNumber() ?? '#',
                $urlGenerator->generate('quote/view', [
                    'id' => $model->reqQuoteId()]), [
                        'class' => 'text-decoration-none']);
        },
        encodeContent: false,
    ),
    new DataColumn(
        'date_created',
        header: $translator->translate('date.created'),
        content: static function (SalesOrder $model): string {
/**
 * @psalm-suppress PossiblyInvalidMethodCall $model->getDateCreated()->format('Y-m-d')
 */
            return $model->getDateCreated() instanceof \DateTimeImmutable ?
                    $model->getDateCreated()->format('Y-m-d') : '';
        },
        encodeContent: true,
    ),
    new DataColumn(
        'client_id',
        header: $translator->translate('client'),
        content: static function (SalesOrder $model): string {
            $clientName = $model->getClient()?->getClientName();
            if (null !== $clientName) {
                return Html::encode($clientName);
            } else {
                return '';
            }
        },
    ),
    new DataColumn(
        'id',
        header: $translator->translate('total'),
        content: function (SalesOrder $model) use ($s, $soaR): string {
            $so_id = $model->reqId();
            $so_amount = (($soaR->repoSalesOrderAmountCount(
                    $so_id) > 0) ? $soaR->repoSalesOrderquery(
                            $so_id) : null);
            return $s->formatCurrency(null !== $so_amount ?
                    $so_amount->getTotal() : 0.00);
        },
    ),
    new DataColumn(
        header: $translator->translate('view'),
        content: static function (SalesOrder $model) use ($urlGenerator, $translator): A {
            // WCAG 1.1.1/2.4.4/4.1.2: icon-only link, empty attributes --
            // no title, no aria-label, nothing. A screen reader announced
            // this as a bare, purposeless "link".
            return Html::a(Html::tag('i', '', ['class' => 'bi-eye']),
                $urlGenerator->generate(
                        'salesorder/view', ['id' => $model->reqId()]), [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('view'),
                    'aria-label' => $translator->translate('view'),
                ]);
        },
    ),
];

$gridSummary
    = $s->gridSummary(
        $paginator,
        $translator,
        (int) $s->getSetting('default_list_limit'),
        $translator->translate('salesorders'),
        (string) $so_statuses[$status]['label'],
    );

$toolbarString
    =  new Form()->post(
            $urlGenerator->generate('salesorder/guest'))->csrf($csrf)->open()
    . $statusBar
    .  new Div()->addClass(
            'float-end m-3')->content(
                $toolbarReset . $autoFitColumns . $resetColumnWidths
            )->encode(false)->render()
    .  new Form()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75 resizable-grid', 'id' =>
    'table-salesorder-guest'])
->columnGrouping(true)
->dataReader($paginator)
->columns(...$columns)
// yii-dataview 1.3's accessibility() opt-in -- no sortable columns here,
// but it still adds aria-current/aria-disabled/aria-label/role="link" to
// the pagination widget below (disabled by default, so none of that
// rendered before enabling it here).
->accessibility(true)
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->header($translator->translate('salesorder'))
->id('w12-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
->summaryTemplate($gridSummary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);
