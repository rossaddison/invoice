<?php

declare(strict_types=1);

use App\Invoice\Entity\Inv;
use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Reader\OrderHelper;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ColumnInterface;
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
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataInvNumberDropDownFilter
 */

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'inv/guest'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();

echo $alert;
?>
<div>
    <h5><?= $translator->translate('i.invoice'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 0]); ?>"
                   class="btn btn-<?= $status == 0 ? $iR->getSpecificStatusArrayClass(0) : 'btn-default' ?>">
                   <?= $iR->getSpecificStatusArrayEmoji(0).' '.$translator->translate('i.all'); ?>
                </a>
                
                <?php // Guests are never sent draft invoices i.e. status = '1' ?>
                
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 2 ? $iR->getSpecificStatusArrayClass(2) : 'btn-default' ?>">
                       <?= $iR->getSpecificStatusArrayEmoji(2).' '.$translator->translate('i.sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 3 ? $iR->getSpecificStatusArrayClass(3) : 'btn-default' ?>">
                       <?= $iR->getSpecificStatusArrayEmoji(3).' '.$translator->translate('i.viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 4 ? $iR->getSpecificStatusArrayClass(4) : 'btn-default' ?>">
                       <?= $iR->getSpecificStatusArrayEmoji(4).' '.$translator->translate('i.paid'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 5 ? $iR->getSpecificStatusArrayClass(5) : 'btn-default' ?>">
                    <?= $iR->getSpecificStatusArrayEmoji(5).' '.$translator->translate('i.overdue'); ?>
                </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 6 ? $iR->getSpecificStatusArrayClass(6) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(6).' '.$translator->translate('i.unpaid'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 7 ? $iR->getSpecificStatusArrayClass(7) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(7).' '.$translator->translate('i.reminder'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 8 ? $iR->getSpecificStatusArrayClass(8) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(8).' '.$translator->translate('i.letter'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 9 ? $iR->getSpecificStatusArrayClass(9) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(9).' '.$translator->translate('i.claim'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 10 ? $iR->getSpecificStatusArrayClass(10) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(10).' '.$translator->translate('i.judgement'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 11 ? $iR->getSpecificStatusArrayClass(11) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(11).' '.$translator->translate('i.enforcement'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 12 ? $iR->getSpecificStatusArrayClass(12) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(12).' '.$translator->translate('i.credit_invoice_for_invoice'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 13 ? $iR->getSpecificStatusArrayClass(13) : 'btn-default' ?>">
                     <?= $iR->getSpecificStatusArrayEmoji(13).' '.$translator->translate('i.loss'); ?>
                 </a>
            </div>
    </div>
</div>
<br>
<?php
    /**
     * @var ColumnInterface[] $columns
     */
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (Inv $model) => $model->getId(),
            withSorting: true
        ),
        new ActionColumn(buttons: [
                new ActionButton(
                   url: static function(Inv $model) use ($translator, $urlGenerator) : string {
                       return $urlGenerator->generate('inv/pdf', ['include' => 0]);     
                   },
                   attributes: [
                       'style' => 'text-decoration:none',
                       'data-bs-toggle' => 'tooltip',
                       'title' => $translator->translate('i.download_pdf'),
                       'class' => 'bi bi-file-pdf'
                   ]        
                ),
                new ActionButton(
                   url: static function(Inv $model) use ($translator, $urlGenerator) : string {
                       return $urlGenerator->generate('inv/pdf', ['include' => 1]);     
                   },
                   attributes: [
                       'style' => 'text-decoration:none',
                       'data-bs-toggle' => 'tooltip',
                       'title' => $translator->translate('i.download_pdf').'➡️'.$translator->translate('invoice.custom.field'),
                       'class' => 'bi bi-file-pdf-fill'    
                   ],        
                ),                    
            ]
        ),  
        new DataColumn(
            'status_id',
            header: $translator->translate('i.status'),
            content: static function (Inv $model) use ($s, $iR, $irR, $translator): Yiisoft\Html\Tag\CustomTag {
                $label = $iR->getSpecificStatusArrayLabel((string)$model->getStatus_id());
                if (($model->getIs_read_only()) && $s->getSetting('disable_read_only') === (string) 0) {
                    $label .=  ' 🚫';
                }
                if ($irR->repoCount((string) $model->getId()) > 0) {
                    $label .= $translator->translate('i.recurring'). ' 🔄';
                }
                return Html::tag('span', $iR->getSpecificStatusArrayEmoji((int)$model->getStatus_id()). $label, ['class' => 'label label-' . $iR->getSpecificStatusArrayClass((int)$model->getStatus_id())]);
            },
            withSorting: true
        ),
        new DataColumn(
            field: 'number',
            property: 'filterInvNumber',       
            header: $translator->translate('invoice.invoice.number'),    
            content: static function (Inv $model) use ($urlGenerator, $iR): string {
                $creditInvoiceUrl = '';
                $creditInvoiceParentId = $model->getCreditinvoice_parent_id();
                if ($creditInvoiceParentId > 0)  {
                    // include a path to the parent invoice as well as the credit note/invoice
                    $inv = $iR->repoInvUnLoadedquery($creditInvoiceParentId);
                    if (null!==$inv) {
                    $creditInvoiceUrl = '⬅️'.Html::a($inv->getNumber() ?? '#', $urlGenerator->generate('inv/view', 
                            ['id' => $creditInvoiceParentId]
                         ),
                         [
                            'style' => 'text-decoration:none'
                         ])->render();
                    }
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
            'client_id',
            header: $translator->translate('i.client'),    
            content: static fn (Inv $model): string => $model->getClient()?->getClient_name() ?? '',
            withSorting: false    
        ),
        new DataColumn(                
            'date_created',
            header: $translator->translate('i.date_created'),    
            content: static fn (Inv $model): string => (!is_string($dateCreated = $model->getDate_created()) ? $dateCreated->format($dateHelper->style()) : ''),
            withSorting: false    
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
            withSorting: true        
        ),        
        new DataColumn(
            field: 'id',
            property: 'filterInvAmountTotal',
            header: $translator->translate('i.total') . ' ( '. $s->getSetting('currency_symbol'). ' ) ',
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
            filter: \Yiisoft\Yii\DataView\Filter\Widget\TextInputFilter::widget()
                    ->addAttributes(['style' =>'max-width: 50px']),
            withSorting: false        
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.paid') . ' ( '. $s->getSetting('currency_symbol'). ' ) ',
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
            header: $translator->translate('i.balance')  . ' ( '. $s->getSetting('currency_symbol'). ' ) ',
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
    ]            
?>
<?php
    $sort = Sort::only(['status_id', 'number', 'date_created', 'date_due', 'id', 'client_id'])
            ->withOrderString($sortString);
    
    $sortedAndPagedPaginator = (new OffsetPaginator($invs))
                        ->withPageSize($userInvListLimit ?: 10)
                        ->withCurrentPage($page)
                        ->withSort($sort)  
                        ->withToken(PageToken::next((string)$page));   
    
          
    $toolbarString = Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Div::tag()->addClass('float-end m-3')->content(Button::ascDesc($urlGenerator, 'client_id', 'warning', $translator->translate('i.client'), true))->encode(false)->render().    
            Form::tag()->close();
    
    $grid_summary = $s->grid_summary(
        $sortedAndPagedPaginator,
        $translator,
        !empty($userInvListLimit) ? $userInvListLimit : 10,
        $translator->translate('invoice.invoice.invoices'),
        $label
    );
    
    $urlCreator = new UrlCreator($urlGenerator);
    $order =  OrderHelper::stringToArray($sortString);
    $urlCreator->__invoke([], $order); 
    
    echo GridView::widget()
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invoice-guest'])
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
        ->headerTableEnabled(true)        
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->footerEnabled(true) 
        ->emptyCell($translator->translate('i.not_set'))
        ->emptyCellAttributes(['style' => 'color:red'])  
        ->header($gridComponents->header(' ' . $translator->translate('i.invoice')))
        ->id('w9-grid')
        ->pagination(
           $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $sortedAndPagedPaginator)
        )
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate(($viewInv ? 
                           $pageSizeLimiter::buttonsGuest($userInv, $urlGenerator, $translator, 'inv', $defaultPageSizeOffsetPaginator) : '').' '.
                           $grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText($translator->translate('invoice.invoice.no.records')) 
        ->toolbar($toolbarString);
?>
