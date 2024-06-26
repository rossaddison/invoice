<?php
declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;

/**
 * @see config/common/params.php 'yiisoft/view => ['gridComponents' => Reference::to(GridComponents::class)]',
 */

/**
 * @var \App\Invoice\Entity\Inv $inv
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var TranslatorInterface $translator
 * @var WebView $this 
 */
?>
<?= $alert; ?>
<?php


$toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-primary me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName()))
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
        <?php if ($client_count === 0) { ?>
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
    <br>
    <div class="submenu-row">
        <!--  Route::get('/inv[/page/{page:\d+}[/status/{status:\d+}]]') -->
        <div class="btn-group index-options">
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 0]); ?>"
               class="btn btn-<?= $status == 0 ? $inv_statuses['0']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['0']['emoji'].' '.$translator->translate('i.all'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 1]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 1 ? $inv_statuses['1']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['1']['emoji'].' '.$translator->translate('i.draft'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 2 ? $inv_statuses['2']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['2']['emoji'].' '.$translator->translate('i.sent'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 3 ? $inv_statuses['3']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['3']['emoji'].' '.$translator->translate('i.viewed'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 4 ? $inv_statuses['4']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['4']['emoji'].' '.$translator->translate('i.paid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 5 ? $inv_statuses['5']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['5']['emoji'].' '.$translator->translate('i.overdue'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 6 ? $inv_statuses['6']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['6']['emoji'].' '.$translator->translate('i.unpaid'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 7 ? $inv_statuses['7']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['7']['emoji'].' '.$translator->translate('i.reminder'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 8 ? $inv_statuses['8']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['8']['emoji'].' '.$translator->translate('i.letter'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 9 ? $inv_statuses['9']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['9']['emoji'].' '.$translator->translate('i.claim'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 10 ? $inv_statuses['10']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['10']['emoji'].' '.$translator->translate('i.judgement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 11 ? $inv_statuses['11']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['11']['emoji'].' '.$translator->translate('i.enforcement'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 12 ? $inv_statuses['12']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['12']['emoji'].' '.$translator->translate('i.credit_invoice_for_invoice'); ?>
            </a>
            <a href="<?= $urlGenerator->generate('inv/index', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
               class="btn btn-<?= $status == 13 ? $inv_statuses['13']['class'] : 'btn-default' ?>">
                <?= $inv_statuses['13']['emoji'].' '.$translator->translate('i.loss'); ?>
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
        new DataColumn(
            'id',    
            header: $translator->translate('i.id'),
            content: static fn(object $any) => $any->getId(),
            // remove the hyperlinked sort header. Using customized sort button    
            withSorting: false
        ),
        new DataColumn(
            content: static function ($model) use ($urlGenerator): string {
                return Html::a('🔍', $urlGenerator->generate('inv/view', 
                               ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render();
            }  
        ),        
        new DataColumn(
            content: static function ($model) use ($s, $urlGenerator): string {
                return $model->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string) 0 
                       ? Html::a('🖉', $urlGenerator->generate('inv/edit', 
                               ['id' => $model->getId()]), ['style' => 'text-decoration:none'])->render() 
                       : '';
            }     
        ),
        new DataColumn(
            'invsentlogs',
            header: $translator->translate('invoice.email.logs.with.filter'),    
            content: static function ($model) use ($islR, $toggleColumnInvSentLog, $urlGenerator, $translator) : string {
                $count = $islR->repoInvSentLogEmailedCountForEachInvoice($model->getId());
                $linkToInvSentLogWithFilterInv = A::tag()
                ->addAttributes(['type' => 'reset', 'data-bs-toggle' => 'tooltip', 'title' => $translator->translate('invoice.email.logs')])
                ->addClass('btn btn-success me-1')
                ->content((string)$count)
                ->href($urlGenerator->generate('invsentlog/index', [], ['filterInvNumber' => $model->getNumber()]))
                ->id('btn-all-visible')
                ->render();
                if ($count > 0) {
                    return $toggleColumnInvSentLog.$linkToInvSentLogWithFilterInv;
                } else {
                    return '';
                }
            }    
        ),        
        new DataColumn(
            'invsentlogs',
            header: '',    
            content: static function ($model) use ($islR, $urlGenerator, $gridComponents) : string {
                $invsentlogs = $islR->repoInvSentLogForEachInvoice($model->getId());
                $model->setInvSentLogs();
                foreach ($invsentlogs as $invsentlog) {
                    $model->addInvSentLog($invsentlog);
                }
                return $gridComponents->gridMiniTableOfInvSentLogsForInv(
                    $model, 
                    $min_invsentlogs_per_row = 4, 
                    $urlGenerator
                );
            },
            visible: $visibleToggleInvSentLogColumn,        
        ),
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function ($model) use ($s, $irR, $inv_statuses, $translator): Yiisoft\Html\Tag\CustomTag {
                $label = $inv_statuses[(string) $model->getStatus_id()]['label'];
                if (($model->getIs_read_only()) && $s->get_setting('disable_read_only') === (string) 0) {
                    $label =  $translator->translate('i.paid'). ' 🚫';
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $label = $translator->translate('i.recurring'). ' 🔄';
                }
                return Html::tag('span', $inv_statuses[(string) $model->getStatus_id()]['emoji']. $label, ['class' => 'label label-' . $inv_statuses[(string) $model->getStatus_id()]['class']]);
            },
            withSorting: false
        ),
        new DataColumn(
            field: 'number',
            property: 'filterInvNumber',       
            header: $translator->translate('invoice.invoice.number'),    
            content: static function ($model) use ($urlGenerator, $iR): string {
                $creditInvoiceUrl = '';
                if ((int)$model->getCreditinvoice_parent_id() > 0)  {
                        // include a path to the parent invoice as well as the credit note/invoice
                        $inv = $iR->repoInvUnLoadedquery($model->getCreditinvoice_parent_id());
                        $creditInvoiceUrl = '⬅️'.Html::a($inv->getNumber(), $urlGenerator->generate('inv/view', 
                                                       ['id' => $model->getCreditinvoice_parent_id()]
                                                    ),
                                                    [
                                                       'style' => 'text-decoration:none'
                                                    ])->render();
                }
                return  Html::a($model->getNumber(), $urlGenerator->generate('inv/view', ['id' => $model->getId()]),
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
            content: static fn($model): string => (string)$model->getClient()->getClient_full_name(),
            filter: $optionsDataClientsDropdownFilter,
            withSorting: false
        ),
        new DataColumn(
            field: 'client_id',
            property: 'filterClientGroup', 
            header: $translator->translate('invoice.client.group'),
            content: static fn($model): string => $model->getClient()->getClient_group() ?? '',
            filter: $optionsDataClientGroupDropDownFilter,
            withSorting: false    
        ), 
        new DataColumn(
            field: 'date_created',
            property: 'filterDateCreatedYearMonth',  
            header: $translator->translate('invoice.datetime.immutable.date.created.mySql.format.year.month.filter'),
            content: static fn($model): string => ($model->getDate_created())->format('Y-m-d'),
            filter: $optionsDataYearMonthDropDownFilter,
            withSorting: false    
        ),           
        new DataColumn(
            'time_created',
            header: $translator->translate('invoice.datetime.immutable.time.created'),
            // Show only the time of the DateTimeImmutable    
            content: static fn($model): string => ($model->getTime_created())->format('H:i:s')
        ),              
        new DataColumn(
            'date_modified',
            header: $translator->translate('invoice.datetime.immutable.date.modified'),
            content: static function ($model) use ($dateHelper) : string  {
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
            content: static function($model) use ($datehelper) : string {
                $now = new \DateTimeImmutable('now');
                return Label::tag()
                        ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode($model->getDate_due()->format($datehelper->style())))
                        ->render();
            },
            withSorting: false
        ),        
        new DataColumn(
            field: 'id',
            property: 'filterInvAmountTotal',
            header: $translator->translate('i.total') . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function ($model) use ($decimal_places) : string|null {
               return  
                    Label::tag()
                        ->attributes(['class' => $model->getInvAmount()->getTotal() > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null!==$model->getInvAmount()->getTotal() 
                                ? number_format($model->getInvAmount()->getTotal() , $decimal_places) 
                                : number_format(0, $decimal_places)))
                        ->render();
            },
            filter: true,
            withSorting: false
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.paid') . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function ($model) use ($decimal_places) : string|null {
                return Label::tag()
                        ->attributes(['class' => $model->getInvAmount()->getPaid() < $model->getInvAmount()->getTotal() ? 'label label-danger' : 'label label-success'])
                        ->content(Html::encode(null!==$model->getInvAmount()->getPaid() 
                                ? number_format($model->getInvAmount()->getPaid(),  $decimal_places) 
                                : number_format(0, $decimal_places)))
                        ->render();
            },
            withSorting: false        
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.balance')  . ' ( '. $s->get_setting('currency_symbol'). ' ) ',
            content: static function ($model) use ($decimal_places) : string|null {
                return  Label::tag()
                        ->attributes(['class' => $model->getInvAmount()->getBalance() > 0.00 ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode(null!== $model->getInvAmount() 
                                ? number_format($model->getInvAmount()->getBalance(), $decimal_places) 
                                : number_format(0, $decimal_places)))
                        ->render();
            },
            withSorting: false
        ),
        new DataColumn(
            header: '🚚',
            content: static function ($model) use ($urlGenerator): string {
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
            content: static function ($model) use ($translator, $urlGenerator, $qR): string {
                $quote_id = $model->getQuote_id();
                $quote = $qR->repoQuoteUnloadedquery($quote_id);
                if ($quote) {
                    return (string) Html::a($quote->getNumber() . ' ' . (string) $qR->getStatuses($translator)[$quote->getStatus_id()]['label'], $urlGenerator->generate('quote/view', ['id' => $quote_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . (string) $qR->getStatuses($translator)[$quote->getStatus_id()]['class']]);
                } else {
                    return '';
                }
            },
            visible: $visible,
            withSorting: false        
        ),
        new DataColumn(
            'so_id',
            header: $translator->translate('invoice.salesorder.number.status'),
            content: static function ($model) use ($s, $urlGenerator, $soR): string {
                $so_id = $model->getSo_id();
                $so = $soR->repoSalesOrderUnloadedquery($so_id);
                if ($so) {
                    return (string) Html::a($so->getNumber() . ' ' . (string) $soR->getStatuses($s)[$so->getStatus_id()]['label'], $urlGenerator->generate('salesorder/view', ['id' => $so_id]), ['style' => 'text-decoration:none', 'class' => 'label ' . (string) $soR->getStatuses($s)[$so->getStatus_id()]['class']]);
                } else {
                    return '';
                }
            },
            visible: $visible,
            withSorting: false        
        ), 
        new DataColumn(
            'delivery_location_id',
            header: $translator->translate('invoice.delivery.location.global.location.number'),
            content: static function ($model) use ($dlR): string|null {
                $delivery_location_id = $model->getDelivery_location_id();
                $delivery_location = (($dlR->repoCount($delivery_location_id) > 0) ? $dlR->repoDeliveryLocationquery($delivery_location_id) : null);
                return null !== $delivery_location ? $delivery_location->getGlobal_location_number() : '';
            },
            visible: $visible,
            withSorting: false        
        ),
        new DataColumn(
            header: $translator->translate('i.delete'),
            content: static function ($model) use ($s, $translator, $urlGenerator): string {
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
<?php  echo GridView::widget()
    // unpack the contents within the array using the three dot splat operator    
    ->columns(...$columns)
    ->dataReader($paginator)
    ->header($gridComponents->header(' ' . $translator->translate('i.invoice')))
    ->headerRowAttributes(['class' => 'card-header bg-info text-black']) 
    ->id('w3-grid') 
    ->pagination(
        $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
    )
    ->rowAttributes(['class' => 'align-left'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    /**
     * @see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in inv/index
     */    
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'inv').' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string) $translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped h-75', 'id' => 'table-invoice'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('inv/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($allVisible)->encode(false)->render() .  
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'date_due', 'danger', $translator->translate('i.due_date'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('i.client'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'status_id', 'success', $translator->translate('i.status'), false))->encode(false)->render().    
        Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'id', 'info', $translator->translate('i.id'), false))->encode(false)->render().
        Form::tag()->close()    
    )    
?>

<?php echo $modal_add_inv; ?>
