<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Input\Checkbox;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Yii\DataView\Column\Base\DataContext;
use Yiisoft\Yii\DataView\Column\CheckboxColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @see config/common/params.php 'yiisoft/view => ['gridComponents' => Reference::to(GridComponents::class)]',
 */

/**
 * @var App\Invoice\DeliveryLocation\DeliveryLocationRepository $dlR
 * @var App\Invoice\Entity\Inv $inv
 * @var App\Invoice\Entity\UserInv $userInv
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Inv\InvRepository $iR
 * @var App\Invoice\InvRecurring\InvRecurringRepository $irR
 * @var App\Invoice\InvSentLog\InvSentLogRepository $islR
 * @var App\Invoice\Quote\QuoteRepository $qR
 * @var App\Invoice\SalesOrder\SalesOrderRepository $soR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var bool $visible
 * @var bool $visibleToggleInvSentLogColumn
 * @var int $clientCount
 * @var int $decimalPlaces
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf
 * @var string $label
 * @var string $modal_add_inv
 * @var string $status
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientsDropdownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClientGroupDropDownFilter
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataYearMonthDropDownFilter
 */

?>
<?= $alert; ?>
<?php

$toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-primary me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/index'))
        ->id('btn-reset')
        ->render();

$allVisible = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.hide.or.unhide.columns')])
        ->addClass('btn btn-warning me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/visible'))
        ->id('btn-all-visible')
        ->render();

$toggleColumnInvSentLog = A::tag()
        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.hide.or.unhide.columns')])
        ->addClass('btn btn-info me-1 ajax-loader')
        ->content('↔️')
        ->href($urlGenerator->generate('setting/toggleinvsentlogcolumn'))
        ->id('btn-all-visible')
        ->render();

$toolbar = Div::tag();
?>
<div>
    <h5><?= $translator->translate('i.invoice'); ?></h5>
    <div class="btn-group">
        <?php if ($clientCount === 0) { ?>
            <a href="#modal-add-inv" class="btn btn-success" data-toggle="modal" disabled data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_client'); ?>">
                <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
            </a>
        <?php } else { ?>
            <a href="#modal-add-inv" class="btn btn-success" data-toggle="modal">
                <i class="fa fa-plus"></i><?= $translator->translate('i.new'); ?>
            </a>
        <?php } ?>
    </div>
    <br>
    <?php 
        /**
         * Mark the invoice as Sent
         * @see $button::statusMark($generator, $iR, $status, $translated, $guest) 
         */
        //echo $button::statusMark($urlGenerator, $iR, 2, $translator->translate('i.sent'), false);
    ?>
    <br>
    <div class="submenu-row">
        <!--  Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]') -->
        <div class="btn-group index-options">
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 0]); ?>"
               class="btn btn-<?= $status == 0 ? $iR->getSpecificStatusArrayClass(0) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(0).' '.$translator->translate('i.all'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 1]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 1 ? $iR->getSpecificStatusArrayClass(1) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(1).' '.$translator->translate('i.draft'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 2 ? $iR->getSpecificStatusArrayClass(2) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(2).' '.$translator->translate('i.sent'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 3 ? $iR->getSpecificStatusArrayClass(3) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(3).' '.$translator->translate('i.viewed'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 4 ? $iR->getSpecificStatusArrayClass(4) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(4).' '.$translator->translate('i.paid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 5 ? $iR->getSpecificStatusArrayClass(5) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(5).' '.$translator->translate('i.overdue'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 6 ? $iR->getSpecificStatusArrayClass(6) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(6).' '.$translator->translate('i.unpaid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 7 ? $iR->getSpecificStatusArrayClass(7) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(7).' '.$translator->translate('i.reminder'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 8 ? $iR->getSpecificStatusArrayClass(8) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(8).' '.$translator->translate('i.letter'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 9 ? $iR->getSpecificStatusArrayClass(9) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(9).' '.$translator->translate('i.claim'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 10 ? $iR->getSpecificStatusArrayClass(10) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(10).' '.$translator->translate('i.judgement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 11 ? $iR->getSpecificStatusArrayClass(11) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(11).' '.$translator->translate('i.enforcement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 12 ? $iR->getSpecificStatusArrayClass(12) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(12).' '.$translator->translate('i.credit_invoice_for_invoice'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 13 ? $iR->getSpecificStatusArrayClass(13) : 'btn-default' ?>">
                <?= $iR->getSpecificStatusArrayEmoji(13).' '.$translator->translate('i.loss'); ?>
            </a>
        </div>
    </div>
    <br>
</div>

<?php 
    /**
     * @var ColumnInterface[] $columns
     */
    $columns = [ 
        new CheckboxColumn(
            content: static function(Checkbox $input, DataContext $context) : string {
                $inv = $context->data;
                if (($inv instanceof Inv) && (null!==($id = $inv->getId()))) {
                    return '<input type="checkbox" name="checkbox" value="'.$id.'">';
                }
                return '';
            },
            multiple: true           
        ),      
        new DataColumn(
            'id',    
            header: $translator->translate('i.id'),
            content: static fn(Inv $model) => $model->getId(),
            // remove the hyperlinked sort header. Using customized sort button    
            withSorting: false
        ),
        new DataColumn(
            content: static function (Inv $model) use ($urlGenerator): string {
                return Html::a('🔍', $urlGenerator->generate('inv/view', 
                               ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render();
            }  
        ),        
        new DataColumn(
            content: static function (Inv $model) use ($s, $urlGenerator): string {
                return $model->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string) 0 
                       ? Html::a('🖉', $urlGenerator->generate('inv/edit', 
                               ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render() 
                       : '';
            }     
        ),
        new DataColumn(
            'invsentlogs',
            header: $translator->translate('invoice.email.logs.with.filter'),    
            content: static function (Inv $model) use ($islR, $toggleColumnInvSentLog, $urlGenerator, $translator) : string {
                $modelId = $model->getId();
                if (null!==$modelId) {
                    $count = $islR->repoInvSentLogEmailedCountForEachInvoice($modelId);
                    if ($count > 0) {
                        $linkToInvSentLogWithFilterInv = A::tag()
                        ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.email.logs')])
                        ->addClass('btn btn-success me-1')
                        ->content((string)$count)
                        ->href($urlGenerator->generate('invsentlog/index', [], ['filterInvNumber' => $model->getNumber()]))
                        ->id('btn-all-visible')
                        ->render();
                        return $toggleColumnInvSentLog.$linkToInvSentLogWithFilterInv;
                    } else {
                        return '';
                    }
                }
                return '';
            }    
        ),        
        new DataColumn(
            'invsentlogs',
            header: '',    
            content: static function (Inv $model) use ($islR, $urlGenerator, $gridComponents) : string {
                $modelId = $model->getId();
                if (null!==$modelId) {
                    $invSentLogs = $islR->repoInvSentLogForEachInvoice($modelId);
                    /**
                     * @see Initialize an ArrayCollection 
                     */
                    $model->setInvSentLogs();
                    /**
                     * @var App\Invoice\Entity\InvSentLog $invSentLog
                     */
                    foreach ($invSentLogs as $invSentLog) {
                        $model->addInvSentLog($invSentLog);
                    }
                    return $gridComponents->gridMiniTableOfInvSentLogsForInv(
                        $model, 
                        $min_invsentlogs_per_row = 4, 
                        $urlGenerator
                    );
                }
                return '';
            },
            visible: $visibleToggleInvSentLogColumn,        
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function (Inv $model) use ($s, $iR, $irR, $translator): Yiisoft\Html\Tag\CustomTag {
                $label = $iR->getSpecificStatusArrayLabel((string)$model->getStatus_id());
                if (($model->getIs_read_only()) && $s->get_setting('disable_read_only') === (string) 0) {
                    $label =  $translator->translate('i.paid'). ' 🚫';
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $label = $translator->translate('i.recurring'). ' 🔄';
                }
                return Html::tag('span', $iR->getSpecificStatusArrayEmoji((int)$model->getStatus_id()). $label, ['class' => 'label label-' . $iR->getSpecificStatusArrayClass((int)$model->getStatus_id())]);
            },
            withSorting: false
        ),
        new DataColumn(
            field: 'number',
            property: 'filterInvNumber',       
            header: $translator->translate('invoice.invoice.number'),    
            content: static function (Inv $model) use ($urlGenerator, $iR): string {
                $creditInvoiceUrl = '';
                if ((int)$model->getCreditinvoice_parent_id() > 0)  {
                        // include a path to the parent invoice as well as the credit note/invoice
                        $inv = $iR->repoInvUnLoadedquery($model->getCreditinvoice_parent_id());
                        if ($inv) {
                            $creditInvoiceUrl = '⬅️'.Html::a($inv->getNumber() ?? '#', $urlGenerator->generate('inv/view', 
                                                       ['id' => $model->getCreditinvoice_parent_id()]
                                                    ),
                                                    [
                                                       'style' => 'text-decoration:none'
                                                    ])->render();
                        }
                        $creditInvoiceUrl = '';
                }
                return  Html::a($model->getNumber() ?? '#', $urlGenerator->generate('inv/view', ['id' => $model->getId()]),
                        [
                           'style' => 'text-decoration:none'
                        ])->render() . 
                        $creditInvoiceUrl;
            },
            filter: $optionsDataInvNumberDropDownFilter,
            withSorting: false        
        ),      
        new DataColumn(
            field: 'client_id',
            property: 'filterClient',
            header: $translator->translate('i.client'),
            content: static fn(Inv $model): string => (string)$model->getClient()?->getClient_full_name(),
            filter: $optionsDataClientsDropdownFilter,
            withSorting: false
        ),
        new DataColumn(
            field: 'client_id',
            property: 'filterClientGroup', 
            header: $translator->translate('invoice.client.group'),
            content: static fn(Inv $model): string => $model->getClient()?->getClient_group() ?? '',
            filter: $optionsDataClientGroupDropDownFilter,
            withSorting: false    
        ), 
        new DataColumn(
            field: 'date_created',
            property: 'filterDateCreatedYearMonth',  
            header: $translator->translate('invoice.datetime.immutable.date.created.mySql.format.year.month.filter'),
            content: static fn(Inv $model): string => ($model->getDate_created())->format('Y-m-d'),
            filter: $optionsDataYearMonthDropDownFilter,
            withSorting: false    
        ),           
        new DataColumn(
            'time_created',
            header: $translator->translate('invoice.datetime.immutable.time.created'),
            // Show only the time of the DateTimeImmutable    
            content: static fn(Inv $model): string => ($model->getTime_created())->format('H:i:s')
        ),              
        new DataColumn(
            'date_modified',
            header: $translator->translate('invoice.datetime.immutable.date.modified'),
            content: static function (Inv $model) use ($dateHelper) : string  {
                if ($model->getDate_modified() <> $model->getDate_created()) {
                    return Label::tag()
                           ->attributes(['class' => 'label label-danger'])
                           ->content(Html::encode($model->getDate_modified()->format($dateHelper->style())))
                           ->render(); 
                } else {
                    return Label::tag()
                           ->attributes(['class' => 'label label-success'])
                           ->content(Html::encode($model->getDate_modified()->format($dateHelper->style())))
                           ->render(); 
                } 
            } 
        ), 
        new DataColumn(
            'date_due',
            header: $translator->translate('i.due_date'),           
            content: static function(Inv $model) use ($dateHelper) : string {
                $now = new \DateTimeImmutable('now');
                return Label::tag()
                        ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(!is_string($dateDue = $model->getDate_due())? $dateDue->format($dateHelper->style()) : ''))
                        ->render();
            },
            withSorting: false        
        ),  
        new DataColumn(
            field: 'id',
            property: 'filterInvAmountTotal',
            header: $translator->translate('i.total') . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function (Inv $model) use ($decimalPlaces) : string {
                $invAmountTotal = $model->getInvAmount()->getTotal();
                return
                    Label::tag()
                        ->attributes(['class' => $invAmountTotal > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null!==$invAmountTotal 
                                ? number_format($invAmountTotal , $decimalPlaces) 
                                : number_format(0, $decimalPlaces)))
                        ->render();
            },
            filter: true,
            withSorting: false        
        ),       
        new DataColumn(
            'id',
            header: $translator->translate('i.paid') . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function (Inv $model) use ($decimalPlaces) : string {
                $invAmountPaid = $model->getInvAmount()->getPaid();
                return Label::tag()
                        ->attributes(['class' => $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal() ? 'label label-danger' : 'label label-success'])
                        ->content(Html::encode(null!==$invAmountPaid 
                                ? number_format($invAmountPaid > 0.00 ? $invAmountPaid : 0.00, $decimalPlaces) 
                                : number_format(0, $decimalPlaces)))
                        ->render();
            },
            withSorting: false           
        ),         
         new DataColumn(
            'id',
            header: $translator->translate('i.balance')  . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function (Inv $model) use ($decimalPlaces) : string {
                $invAmountBalance = $model->getInvAmount()->getBalance(); 
                return  Label::tag()
                        ->attributes(['class' => $invAmountBalance > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null!==$invAmountBalance 
                                ? number_format($invAmountBalance > 0.00 ? $invAmountBalance : 0.00, $decimalPlaces) 
                                : number_format(0, $decimalPlaces)))
                        ->render();
            },
            withSorting: false     
        ),
        new DataColumn(
            header: '🚚',
            content: static function (Inv $model) use ($urlGenerator): string {
                return Html::a(Html::tag('i', '', ['class' => 'fa fa-plus fa-margin']), $urlGenerator->generate('del/add', [
                    /**
                     * 
                     * @see DeliveryLocation add function getRedirectResponse
                     * @see config/common/routes/routes.php Route::methods([Method::GET, Method::POST], '/del/add/{client_id}[/{origin}/{origin_id}/{action}]')
                     */
                    'client_id' => $model->getClient_id()
                ],
                [
                    'origin' => 'inv',
                    'origin_id' => $model->getId(),
                    'action' => 'index'
                ]))->render();
            },
            visible: $visible,
            withSorting: false        
        ),
        new DataColumn(
            'quote_id',
            header: $translator->translate('invoice.quote.number.status'),
            content: static function (Inv $model) use ($urlGenerator, $qR): string {
                $quote_id = $model->getQuote_id();
                $quote = $qR->repoQuoteUnloadedquery($quote_id);
                if (null!==$quote) {
                    $statusId = $quote->getStatus_id();
                    if (null!==$statusId) {
                        return (string) Html::a(($quote->getNumber() ?? '#') . ' ' . $qR->getSpecificStatusArrayLabel((string)$statusId), $urlGenerator->generate('quote/view', ['id' => $quote_id]), 
                            [
                                'style' => 'text-decoration:none', 
                                'class' => 'label ' . $qR->getSpecificStatusArrayClass((string)$statusId)
                            ]);
                    }   
                } 
                return '';
            },
            visible: $visible,
            withSorting: false        
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('invoice.salesorder.number.status'),
            content: static function (Inv $model) use ($urlGenerator, $soR): string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if (null!==$so) {
                    $statusId = $so->getStatus_id();
                    if (null!==$statusId) {
                        return (string) Html::a(($so->getNumber() ?? '#') . ' ' . $soR->getSpecificStatusArrayLabel((string)$statusId), $urlGenerator->generate('salesorder/view', ['id' => $so_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . $soR->getSpecificStatusArrayClass($statusId)]);
                    }   
                } else {
                    return '';
                }
                return '';
            },
            visible: $visible,
            withSorting: false        
        ), 
        new DataColumn(
            'delivery_location_id',
            header: $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function (Inv $model) use ($dlR): string|null {
                $delivery_location_id = $model->getDelivery_location_id();
                $delivery_location = (($dlR->repoCount($delivery_location_id) > 0) ? $dlR->repoDeliveryLocationquery($delivery_location_id) : null);
                return null !== $delivery_location ? $delivery_location->getGlobal_location_number() : '';
            },
            visible: $visible,
            withSorting: false        
        ),
        new DataColumn(
            header: $translator->translate('i.delete'),
            content: static function (Inv $model) use ($s, $translator, $urlGenerator): string {
                return $model->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string) 0 && $model->getSo_id() === '0' && $model->getQuote_id() === '0' ? Html::a(Html::tag('button',
                        Html::tag('i', '', ['class' => 'fa fa-trash fa-margin']),
                        [
                            'type' => 'submit',
                            'class' => 'dropdown-button',
                            'onclick' => "return confirm(" . "'" . $translator->translate('i.delete_record_warning') . "');"
                        ]
                        ),
                        $urlGenerator->generate('inv/delete', ['id' => $model->getId()]), []
                )->render() : '';
            },
            visible: $visible,
            withSorting: false        
        )   
    ];
?>
<?php
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('inv/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($allVisible)->encode(false)->render() .  
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'date_due', 'danger', $translator->translate('i.due_date'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('i.client'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'status_id', 'success', $translator->translate('i.status'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'id', 'info', $translator->translate('i.id'), false))->encode(false)->render().
        Form::tag()->close();
    $grid_summary = $s->grid_summary(
        $paginator,
        $translator,
        (int) $s->get_setting('default_list_limit'),
        $translator->translate('invoice.invoice.invoices'),
        $label
    );
    echo GridView::widget()
    // unpack the contents within the array using the three dot splat operator
    ->rowAttributes(['class' => 'align-left'])
    ->tableAttributes(['class' => 'table table-striped h-75', 'id' => 'table-invoice'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class' => 'card-header bg-info text-black'])         
    ->header($gridComponents->header(' ' . $translator->translate('i.invoice')))
    ->id('w3-grid') 
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])    
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'inv').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);    
?>

<?php echo $modal_add_inv; ?>
