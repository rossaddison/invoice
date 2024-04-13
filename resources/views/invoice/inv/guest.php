<?php

declare(strict_types=1);

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

echo $alert;

/**
 * @var \App\Invoice\Entity\Inv $inv 
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName()))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<div>
    <h5><?= $translator->translate('i.invoice'); ?></h5>
    <br>
    <div class="submenu-row">
            <div class="btn-group index-options">
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 0]); ?>"
                   class="btn btn-<?= $status == 0 ? $inv_statuses['0']['class'] : 'btn-default' ?>">
                   <?= $inv_statuses['0']['emoji'].' '.$translator->translate('i.all'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 2]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 2 ? $inv_statuses['2']['class'] : 'btn-default' ?>">
                       <?= $inv_statuses['2']['emoji'].' '.$translator->translate('i.sent'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 3]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 3 ? $inv_statuses['3']['class'] : 'btn-default' ?>">
                       <?= $inv_statuses['3']['emoji'].' '.$translator->translate('i.viewed'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 4]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 4 ? $inv_statuses['4']['class'] : 'btn-default' ?>">
                       <?= $inv_statuses['4']['emoji'].' '.$translator->translate('i.paid'); ?>
                </a>
                <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 5]); ?>" style="text-decoration:none"
                   class="btn btn-<?= $status == 5 ? $inv_statuses['5']['class'] : 'btn-default' ?>">
                    <?= $inv_statuses['5']['emoji'].' '.$translator->translate('i.overdue'); ?>
                </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 6]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 6 ? $inv_statuses['6']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['6']['emoji'].' '.$translator->translate('i.unpaid'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 7]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 7 ? $inv_statuses['7']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['7']['emoji'].' '.$translator->translate('i.reminder'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 8]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 8 ? $inv_statuses['8']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['8']['emoji'].' '.$translator->translate('i.letter'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 9]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 9 ? $inv_statuses['9']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['9']['emoji'].' '.$translator->translate('i.claim'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 10]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 10 ? $inv_statuses['10']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['10']['emoji'].' '.$translator->translate('i.judgement'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 11]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 11 ? $inv_statuses['11']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['11']['emoji'].' '.$translator->translate('i.enforcement'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 12]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 12 ? $inv_statuses['12']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['12']['emoji'].' '.$translator->translate('i.credit_invoice_for_invoice'); ?>
                 </a>
                 <a href="<?= $urlGenerator->generate('inv/guest', ['page' => 1, 'status' => 13]); ?>" style="text-decoration:none"
                    class="btn btn-<?= $status == 13 ? $inv_statuses['13']['class'] : 'btn-default' ?>">
                     <?= $inv_statuses['13']['emoji'].' '.$translator->translate('i.loss'); ?>
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
            content: static fn (object $model) => $model->getId(),
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
            }    
        ),
        new DataColumn(
            field: 'number',
            property: 'filterInvNumber',       
            header: '#',
            content: static function ($model) use ($urlGenerator): string {
               return Html::a($model->getNumber(), $urlGenerator->generate('inv/view',['id'=>$model->getId()]),['style'=>'text-decoration:none'])->render();
            },
            filter: $optionsDataInvNumberDropDownFilter
        ),
        new DataColumn(
            'client_id',                
            content: static fn ($model): string => $model->getClient()->getClient_name()                        
        ),
        new DataColumn(                
            'date_created',
            header: $translator->translate('i.date_created'),    
            content: static fn ($model): string => ($model->getDate_created())->format($datehelper->style())                        
        ),
        new DataColumn(
            'date_due',
            content: static function($model) use ($datehelper) : string {
                $now = new \DateTimeImmutable('now');
                return Label::tag()
                        ->attributes(['class' => $model->getDate_due() > $now ? 'label label-success' : 'label label-warning'])
                        ->content(Html::encode($model->getDate_due()->format($datehelper->style())))
                        ->render();
            }   
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
            filter: true
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
            }     
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
            }     
        ),
    ]            
?>
<?= GridView::widget()
        ->columns(...$columns)        
        ->dataReader($paginator)
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($gridComponents->header(' ' . $translator->translate('i.invoice')))
        ->id('w9-grid')
        ->pagination(
           $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate(($viewInv ? 
                           $pageSizeLimiter::buttonsGuest($userinv, $urlGenerator, $translator, 'inv', $defaultPageSizeOffsetPaginator) : '').' '.
                           $grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records')) 
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invoice-guest'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('inv/guest'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
