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
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Router\CurrentRoute;

/**
 * @var \App\Invoice\Entity\Payment $payment  
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */
 
 echo $alert;

?>
<?php

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName()))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<br>
<?php 
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),                
            content: static fn ($model): string => $model->getId()                        
        ),    
        new DataColumn(
            field: 'payment_date',
            property: 'paymentDateFilter',    
            header:  $translator->translate('i.payment_date'),                
            content: static function ($model) use ($datehelper) : string {
                return $model->getPayment_date()->format($datehelper->style());                        
            },                        
            filter: true    
        ),
        new DataColumn(
            field: 'amount',
            property: 'paymentAmountFilter',    
            header:  $translator->translate('i.amount'),
            content: static function ($model) use ($s): string|null {                        
                return $s->format_currency($model->getAmount() ?: 0.00);
            },
            filter: true
        ),
        new DataColumn(
            'note',
            header:  $translator->translate('i.note'),                
            content: static fn ($model): string => $model->getNote()                        
        ),       
        new DataColumn(
            'inv_id',    
            header:  $translator->translate('i.invoice'),
            content: static function ($model) use ($urlGenerator): string {
               return Html::a($model->getInv()?->getNumber() ?? '', $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),['style'=>'text-decoration:none'])->render();
           }                       
        ), 
        new DataColumn(
            'inv_id',
            header:  $translator->translate('i.total'),                
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getTotal() : 0.00);
            }                        
        ),
        new DataColumn(
            header:  $translator->translate('i.paid'),
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getPaid() : 0.00);
            }                        
        ),
        new DataColumn(
            'id',    
            header:  $translator->translate('i.balance'),
            content: static function ($model) use ($s, $iaR) : string|null {
               $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
               return $s->format_currency(null!==$inv_amount ? $inv_amount->getBalance() : 0.00);
            }                        
        ),
        new DataColumn(
            'payment_method_id',
            header:  $translator->translate('i.payment_method'),
            content: static function ($model) : string|null {
               return $model->getPaymentMethod()->getId() ? $model->getPaymentMethod()->getName() : '';
            }                        
        ),      
    ]
?>
<?= GridView::widget()
        ->columns(...$columns)
        ->dataReader($paginator)
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($gridComponents->header(' ' . $translator->translate('i.payment')))
        ->id('w148-grid')
        ->pagination(
            $gridComponents->offsetPaginationWidget(10, $paginator)
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))                         
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-payment-guest'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('payment/guest'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
