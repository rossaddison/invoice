<?php

declare(strict_types=1);

use App\Invoice\Entity\Quote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Yii\DataView\GridView\Column\ActionButton;
use Yiisoft\Yii\DataView\GridView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView\GridView;

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
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var Yiisoft\Yii\DataView\YiiRouter\UrlCreator $urlCreator
 * @var int $defaultPageSizeOffsetPaginator
 * @var array $quoteStatuses
 * @var array $quoteStatuses[$status]
 * @var bool $editInv
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var string $alert
 * @var string $csrf
 * @var string $decimal_places
 * @var string $modal_add_quote
 * @var string $status
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'quote/index'))
    ->id('btn-reset')
    ->render();

$enabledAddQuoteButton = A::tag()
    ->addAttributes([
        'class' => 'btn btn-info',
        'data-bs-toggle' => 'modal',
        'style' => 'text-decoration:none',
    ])
    ->content('➕')
    ->href('#modal-add-quote')
    ->id('btn-enabled-quote-add-button')
    ->render();

$disabledAddQuoteButton = A::tag()
    ->addAttributes([
        'class' => 'btn btn-info',
        'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('add.client'),
        'disabled' => 'disabled',
        'style' => 'text-decoration:none',
    ])
    ->content('➕')
    ->href('#modal-add-quote')
    ->id('btn-disabled-quote-add-button')
    ->render();

echo Breadcrumbs::widget()
     ->links(
         BreadcrumbLink::to(
             label: $translator->translate('default.quote.group'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_group]',
             ),
             active: true,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $defaultQuoteGroup ?? $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.notes'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[default_quote_notes]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('default.quote.notes') ?: $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('quotes.expire.after'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[quotes_expire_after]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('quotes_expire_after') ?: $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('generate.quote.number.for.draft'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[generate_quote_number_for_draft]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('generate_quote_number_for_draft') == '1' ? '✅' : '❌',
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('default.email.template'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[email_quote_template]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => strlen($s->getSetting('email_quote_template')) > 0 ? $s->getSetting('email_quote_template') : $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
         BreadcrumbLink::to(
             label: $translator->translate('pdf.quote.footer'),
             url: $urlGenerator->generate(
                 'setting/tab_index',
                 [],
                 ['active' => 'quotes'],
                 'settings[pdf_quote_footer]',
             ),
             active: false,
             attributes: [
                 'data-bs-toggle' => 'tooltip',
                 'title' => $s->getSetting('pdf_quote_footer') ?: $translator->translate('not.set'),
             ],
             encodeLabel: false,
         ),
     )
     ->listId(false)
     ->render();

$statusBar = Div::tag()
    ->addClass('btn-group index-options')
    ->content(
        Html::a(
            $translator->translate('all'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 0]),
            [
                'class' => 'btn ' . ($status == 0 ? 'btn-primary' : 'btn-default'),
            ],
        )
        . Html::a(
            $translator->translate('draft'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 1]),
            [
                'class' => 'btn ' . ($status == 1 ? 'btn-primary' : 'btn-default'),
                'style' => 'text-decoration:none',
            ],
        )
        . Html::a(
            $translator->translate('sent'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 2]),
            [
                'class' => 'btn ' . ($status == 2 ? 'btn-primary' : 'btn-default'),
                'style' => 'text-decoration:none',
            ],
        )
        . Html::a(
            $translator->translate('viewed'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 3]),
            [
                'class' => 'btn ' . ($status == 3 ? 'btn-primary' : 'btn-default'),
                'style' => 'text-decoration:none',
            ],
        )
        . Html::a(
            $translator->translate('approved'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 4]),
            [
                'class' => 'btn ' . ($status == 4 ? 'btn-primary' : 'btn-default'),
                'style' => 'text-decoration:none',
            ],
        )
        . Html::a(
            $translator->translate('rejected'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 5]),
            [
                'class' => 'btn ' . ($status == 5 ? 'btn-primary' : 'btn-default'),
                'style' => 'text-decoration:none',
            ],
        )
        . Html::a(
            $translator->translate('canceled'),
            $urlGenerator->generate('quote/index', ['page' => 1, 'status' => 6]),
            [
                'class' => 'btn ' . ($status == 6 ? 'btn-primary' : 'btn-default'),
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
        content: static function (Quote $model): string {
            return (string) $model->getId();
        },
        withSorting: true,
    ),
    new ActionColumn(buttons: [
        new ActionButton(
            content: '🔎',
            url: static function (Quote $model) use ($urlGenerator): string {
                return $urlGenerator->generate('quote/view', ['id' => $model->getId()]);
            },
            attributes: [
                'data-bs-toggle' => 'tooltip',
                'title' => $translator->translate('view'),
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
            ],
        ),
        new ActionButton(
            content: static function (Quote $model): string {
                return ($model->getSo_id() == 0) && ($model->getInv_id() == 0)
                ? '❌'
                : '🚫';
            },
            url: static function (Quote $model) use ($urlGenerator): string {
                return ($model->getSo_id() == 0) && ($model->getInv_id() == 0)
                ? $urlGenerator->generate('quote/delete', ['id' => $model->getId()])
                : '';
            },
            attributes: static function (Quote $model) use ($translator): array {
                return ($model->getSo_id() == 0) && ($model->getInv_id() == 0)
                ?  
                [
                    'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('delete.quote.single'),
                ] 
                : [
                    'disabled' => true,
                    'style' => 'background-color:lightblue',
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('delete.quote.derived'),
                ];        
            },
        ),
    ]),
    new DataColumn(
        'status_id',
        header: $translator->translate('status'),
        content: static function (Quote $model) use ($qR): Yiisoft\Html\Tag\CustomTag {
            if (null !== $model->getStatus_id()) {
                $span = $qR->getSpecificStatusArrayLabel((string) $model->getStatus_id());
                $class = $qR->getSpecificStatusArrayClass((string) $model->getStatus_id());
                return Html::tag('span', $span, ['id' => '#quote-index','class' => 'label ' . $class]);
            }
            return Html::tag('span');
        },
        encodeContent: false,
        withSorting: true,
    ),
    new DataColumn(
        'so_id',
        header: $translator->translate('salesorder.number.status'),
        content: static function (Quote $model) use ($urlGenerator, $soR): A {
            $so_id = $model->getSo_id();
            $so = $soR->repoSalesOrderUnloadedquery($so_id);
            if (null !== $so) {
                $number = $so->getNumber();
                $statusId = $so->getStatus_id();
                if (null !== $number && ($statusId > 0)) {
                    return  A::tag()
                        ->addAttributes(['style' => 'text-decoration:none',
                        'class' => 'label ' . $soR->getSpecificStatusArrayClass($statusId)])
                        ->content($number . ' ' . $soR->getSpecificStatusArrayLabel((string) $statusId))
                        ->href($urlGenerator->generate('salesorder/view', ['id' => $so_id]));
                }
                if ($model->getSo_id() === '0' && $model->getStatus_id() === 7) {
                    if ($statusId > 0) {
                        return A::tag()
                        ->addAttributes(['class' => 'btn btn-warning'])
                               ->content($soR->getSpecificStatusArrayLabel((string) $statusId))
                               ->href('');
                    }
                }
            }
            return A::tag();
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
        content: static fn (Quote $model): string => ($model->getDate_created())->format('Y-m-d'),
        withSorting: true,
    ),
    new DataColumn(
        'date_expires',
        content: static fn (Quote $model): string => ($model->getDate_expires())->format('Y-m-d'),
        withSorting: true,
    ),
    new DataColumn(
        'date_required',
        content: static fn (Quote $model): string => ($model->getDate_required())->format('Y-m-d'),
    ),
    new DataColumn(
        property: 'filterClient',
        header: $translator->translate('client'),
        content: static function (Quote $model): string {
            $clientName = $model->getClient()?->getClient_name();
            $clientSurname = $model->getClient()?->getClient_surname();
            if (null !== $clientName && null !== $clientSurname) {
                return Html::encode($clientName . str_repeat(' ', 2) . $clientSurname);
            }
            return '';
        },
        filter: $optionsDataClientsDropdownFilter,
        withSorting: false,
    ),
    new DataColumn(
        property: 'filterQuoteAmountTotal',
        header: $translator->translate('total') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
        content: static function (Quote $model) use ($decimalPlaces): Label {
            $quoteTotal = $model->getQuoteAmount()->getTotal();
            return
                Label::tag()
                    ->attributes(['class' => $model->getQuoteAmount()->getTotal() > 0.00 ? 'label label-success' : 'label label-warning'])
                    ->content(Html::encode(null !== $quoteTotal ? number_format($quoteTotal, $decimalPlaces) : number_format(0, $decimalPlaces)));
        },
        encodeContent: false,
        filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                ->addAttributes(['style' => 'max-width: 50px']),
        withSorting: false,
    ),
];

$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('quotes'),
    '',
);

$toolbarString
    = Form::tag()->post($urlGenerator->generate('quote/guest'))->csrf($csrf)->open()
    . $statusBar
    . Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render()
    . (
        $clientCount == 0
        ? Div::tag()->addClass('float-end m-3')->content($disabledAddQuoteButton)->encode(false)->render()
        : Div::tag()->addClass('float-end m-3')->content($enabledAddQuoteButton)->encode(false)->render()
    )
    . Form::tag()->close();

echo GridView::widget()
->bodyRowAttributes(['class' => 'align-middle'])
->tableAttributes(['class' => 'table table-striped text-center h-75','id' => 'table-quote'])
->dataReader($paginator)
->columns(...$columns)
->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
->headerRowAttributes(['class' => 'card-header bg-info text-black'])
->emptyCell($translator->translate('not.set'))
->emptyCellAttributes(['style' => 'color:red'])
->header($translator->translate('quote'))
->id('w2-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
/**
 * Related logic: see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in inv/index
 */
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'quote') . ' ' . $grid_summary)
->noResultsCellAttributes(['class' => 'card-header bg-warning text-black'])
->noResultsText($translator->translate('no.records'))
->toolbar($toolbarString);

echo $modal_add_quote;
