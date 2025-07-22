<?php

declare(strict_types=1);

use App\Invoice\Entity\Quote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

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

echo $alert;

$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('quote')),
            ),
    )
    ->render();

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

$toolbar = Div::tag();

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

?>

<div>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 0]); ?>"
                   class="btn <?php echo $status == 0 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 1]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 1 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('draft'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 2]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 2 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 3]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 3 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 4]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 4 ? 'btn-primary' : 'btn-default' ?>">
                    <?= $translator->translate('approved'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 5]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 5 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('rejected'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('quote/index', ['page' => 1,'status' => 6]); ?>" style="text-decoration:none"
                   class="btn  <?php echo $status == 6 ? 'btn-primary' : 'btn-default'  ?>">
                    <?= $translator->translate('canceled'); ?>
                </a>
            </div>
    </div>
</div>
<br>

<?php
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
                content: '❌',
                url: static function (Quote $model) use ($urlGenerator): string {
                    return $urlGenerator->generate('quote/delete', ['id' => $model->getId()]);
                },
                attributes: [
                    'title' => $translator->translate('delete'),
                    'onclick' => "return confirm(" . "'" . $translator->translate('delete.record.warning') . "');",
                ],
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
            withSorting: true,
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('salesorder.number.status'),
            content: static function (Quote $model) use ($urlGenerator, $soR): string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if (null !== $so) {
                    $number = $so->getNumber();
                    $statusId = $so->getStatus_id();
                    if (null !== $number && ($statusId > 0)) {
                        return (string) Html::a(
                            $number . ' ' . $soR->getSpecificStatusArrayLabel((string) $statusId),
                            $urlGenerator->generate('salesorder/view', ['id' => $so_id]),
                            ['style' => 'text-decoration:none',
                                'class' => 'label ' . $soR->getSpecificStatusArrayClass($statusId)],
                        );
                    }
                    if ($model->getSo_id() === '0' && $model->getStatus_id() === 7) {
                        if ($statusId > 0) {
                            return (string) Html::a($soR->getSpecificStatusArrayLabel((string) $statusId), '', ['class' => 'btn btn-warning']);
                        }
                        return '';
                    }
                    return '';
                }
                return '';
            },
        ),
        new DataColumn(
            field: 'number',
            property: 'filterQuoteNumber',
            header: $translator->translate('quote.number'),
            content: static function (Quote $model) use ($urlGenerator): A {
                return Html::a($model->getNumber() ?? '#', $urlGenerator->generate('quote/view', ['id' => $model->getId()]), ['style' => 'text-decoration:none']);
            },
            filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                    ->addAttributes(['style' => 'max-width: 80px']),
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('date.created'),
            content: static fn(Quote $model): string => ($model->getDate_created())->format('Y-m-d'),
            withSorting: true,
        ),
        new DataColumn(
            'date_expires',
            content: static fn(Quote $model): string => ($model->getDate_expires())->format('Y-m-d'),
            withSorting: true,
        ),
        new DataColumn(
            'date_required',
            content: static fn(Quote $model): string => ($model->getDate_required())->format('Y-m-d'),
        ),
        new DataColumn(
            field: 'client_id',
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
            field: 'id',
            property: 'filterQuoteAmountTotal',
            header: $translator->translate('total') . ' ( ' . $s->getSetting('currency_symbol') . ' ) ',
            content: static function (Quote $model) use ($decimalPlaces): Label {
                $quoteTotal = $model->getQuoteAmount()->getTotal();
                return
                    Label::tag()
                        ->attributes(['class' => $model->getQuoteAmount()->getTotal() > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null !== $quoteTotal ? number_format($quoteTotal, $decimalPlaces) : number_format(0, $decimalPlaces)));
            },
            filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                    ->addAttributes(['style' => 'max-width: 50px']),
            withSorting: false,
        ),
    ];
?>
<?php
$grid_summary = $s->grid_summary(
    $paginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('quotes'),
    '',
);
$toolbarString =
    Form::tag()->post($urlGenerator->generate('quote/guest'))->csrf($csrf)->open() .
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
    (
        $clientCount == 0
        ? Div::tag()->addClass('float-end m-3')->content($disabledAddQuoteButton)->encode(false)->render()
        : Div::tag()->addClass('float-end m-3')->content($enabledAddQuoteButton)->encode(false)->render()
    ) .
    Form::tag()->close();
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
//->header($header)
->id('w2-grid')
->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
/**
 * Related logic: see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in inv/index
 */
->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'quote') . ' ' . $grid_summary)
->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
->emptyText($translator->translate('no.records'))
->toolbar($toolbarString);
?>

<?php echo $modal_add_quote ?> 
