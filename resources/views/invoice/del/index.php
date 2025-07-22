<?php

declare(strict_types=1);

use App\Invoice\Entity\DeliveryLocation;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @see App\Invoice\DeliveryLocation\DeliveryLocationController function index
 *
 * @var App\Invoice\Client\ClientRepository    $cR
 * @var App\Invoice\Helpers\DateHelper         $dateHelper
 * @var App\Invoice\Inv\InvRepository          $iR
 * @var App\Invoice\Quote\QuoteRepository      $qR
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var App\Widget\GridComponents              $gridComponents
 * @var App\Widget\PageSizeLimiter             $pageSizeLimiter
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $dels
 * @var OffsetPaginator                        $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\CurrentRoute            $currentRoute
 * @var Yiisoft\Router\FastRoute\UrlGenerator  $urlGenerator
 * @var string                                 $alert
 * @var string                                 $csrf
 * @var string                                 $sortString
 * @var string                                 $title
 *
 * @psalm-var positive-int $page
 */
echo $alert;

?>

<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' '.$translator->translate('delivery.location')),
            ),
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'deliverylocation/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<h1><?php echo $translator->translate('delivery.location'); ?></h1>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: 'id',
            content: static fn (DeliveryLocation $model) => (string) $model->getId(),
            withSorting: true,
        ),
        new DataColumn(
            'client_id',
            header: $translator->translate('client'),
            content: static function (DeliveryLocation $model) use ($cR): string {
                if ($cR->repoClientCount($model->getClient_id()) > 0) {
                    return $cR->repoClientquery($model->getClient_id())->getClient_name();
                }

                return '#';
            },
        ),
        new DataColumn(
            'id',
            header: $translator->translate('quote.delivery.location.index.button.list'),
            content: static function (DeliveryLocation $model) use ($urlGenerator, $qR): string {
                $deliveryLocationId = $model->getId();
                if (null !== $deliveryLocationId) {
                    $quotes  = $qR->findAllWithDeliveryLocation($deliveryLocationId);
                    $buttons = '';
                    $button  = '';
                    /**
                     * @var App\Invoice\Entity\Quote $quote
                     */
                    foreach ($quotes as $quote) {
                        $quoteId = $quote->getId();
                        if (null !== $quoteId) {
                            $button = (string) Html::a(
                                ($quote->getNumber() ?? '#').
                                       ' '.
                                       $quote->getDate_created()->format('Y-m-d'),
                                $urlGenerator->generate('quote/view', ['id' => $quoteId]),
                                ['class'             => 'btn btn-primary btn-sm',
                                    'data-bs-toggle' => 'tooltip',
                                    'title'          => $quoteId,
                                ],
                            );
                            $buttons .= $button.str_repeat('&nbsp;', 1);
                        }
                    }

                    return $buttons;
                }

                return '';
            },
            withSorting: true,
            encodeContent: false,
        ),
        new DataColumn(
            'id',
            header: $translator->translate('delivery.location.index.button.list'),
            content: static function (DeliveryLocation $model) use ($urlGenerator, $iR): string {
                $deliveryLocationId = $model->getId();
                if (null !== $deliveryLocationId) {
                    $invoices = $iR->findAllWithDeliveryLocation($deliveryLocationId);
                    $buttons  = '';
                    $button   = '';
                    /**
                     * @var App\Invoice\Entity\Inv $invoice
                     */
                    foreach ($invoices as $invoice) {
                        $invoiceId = $invoice->getId();
                        if (null !== $invoiceId) {
                            $button = (string) Html::a(
                                ($invoice->getNumber() ?? '#').
                                    ' '.
                                    $invoice->getDate_created()->format(
                                        'Y-m-d',
                                    ),
                                $urlGenerator->generate(
                                    'inv/view',
                                    ['id' => $invoiceId],
                                ),
                                ['class'             => 'btn btn-primary btn-sm',
                                    'data-bs-toggle' => 'tooltip',
                                    'title'          => $invoiceId,
                                ],
                            );
                            $buttons .= $button.str_repeat('&nbsp;', 1);
                        }
                    }

                    return $buttons;
                }

                return '';
            },
            withSorting: true,
            encodeContent: false,
        ),
        new DataColumn(
            'global_location_number',
            header: $translator->translate('delivery.location.global.location.number'),
            content: static function (DeliveryLocation $model): string {
                return $model->getGlobal_location_number() ?? '';
            },
            encodeContent: true,
        ),
        new DataColumn(
            'date_created',
            header: $translator->translate('date.created'),
            content: static fn (DeliveryLocation $model): string => $model->getDate_created()->format(
                'Y-m-d',
            ),
        ),
        new DataColumn(
            header: $translator->translate('view'),
            content: static function (DeliveryLocation $model) use ($urlGenerator): string {
                return Html::a(
                    Html::tag('i', '', ['class' => 'fa fa-eye fa-margin']),
                    $urlGenerator->generate('del/view', ['id' => $model->getId()]),
                    [
                    ],
                )->render();
            },
        ),
        new DataColumn(
            header: $translator->translate('edit'),
            content: static function (DeliveryLocation $model) use ($urlGenerator): string {
                return Html::a(
                    Html::tag('i', '', ['class' => 'fa fa-pencil fa-margin']),
                    $urlGenerator->generate(
                        'del/edit',
                        ['id'     => $model->getId()],
                        ['origin' => 'del', 'origin_id' => '', 'action' => 'index'],
                    ),
                    [],
                )->render();
            },
        ),
        new DataColumn(
            header: $translator->translate('delete'),
            content: static function (DeliveryLocation $model) use ($translator, $urlGenerator): string {
                return Html::a(
                    Html::tag(
                        'button',
                        Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                        [
                            'type'    => 'submit',
                            'class'   => 'dropdown-button',
                            'onclick' => 'return confirm('."'".$translator->translate('delete.record.warning')."');",
                        ],
                    ),
                    $urlGenerator->generate('del/delete', ['id' => $model->getId()]),
                    [],
                )->render();
            },
        ),
    ];
?>
<?php
$urlCreator = new UrlCreator($urlGenerator);
$urlCreator->__invoke([], OrderHelper::stringToArray($sortString));
$defaultPageSizeOffsetPaginator = (int) $s->getSetting('default_list_limit');

$sort = Sort::only(['id'])
    // (@see vendor\yiisoft\data\src\Reader\Sort
    // - => 'desc'  so -id => default descending on id
    ->withOrderString($sortString);

$sortedAndPagedPaginator = (new OffsetPaginator($dels))
    ->withPageSize($defaultPageSizeOffsetPaginator > 0 ? $defaultPageSizeOffsetPaginator : 1)
    ->withCurrentPage($page)
    ->withSort($sort)
    ->withToken(PageToken::next((string) $page));

$grid_summary = $s->grid_summary(
    $sortedAndPagedPaginator,
    $translator,
    (int) $s->getSetting('default_list_limit'),
    $translator->translate('delivery.location.plural'),
    '',
);
$toolbarString = Form::tag()->post($urlGenerator->generate('del/index'))->csrf($csrf)->open().
    Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render().
    Form::tag()->close();

echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-191', 'id' => 'table-delivery'])
    ->columns(...$columns)
    ->dataReader($sortedAndPagedPaginator)
    ->urlCreator($urlCreator)
// the up and down symbol will appear at first indicating that the column can be sorted
// Ir also appears in this state if another column has been sorted
    ->sortableHeaderPrepend('<div class="float-end text-secondary text-opacity-50">⭥</div>')
// the up arrow will appear if column values are ascending
    ->sortableHeaderAscPrepend('<div class="float-end fw-bold">⭡</div>')
// the down arrow will appear if column values are descending
    ->sortableHeaderDescPrepend('<div class="float-end fw-bold">⭣</div>')
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])
    ->header($header)
    ->id('w341-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $translator, $urlGenerator, 'del').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('no.records'))
    ->toolbar($toolbarString);
