<?php
declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Router\CurrentRoute;

/**
 * @var \App\Invoice\Entity\Payment $payment  * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('i.payment'))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName()))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>

<?php if ($canEdit && $canView) { ?>
    <div>
     <h5><?= $translator->translate('i.payment'); ?></h5>
     <a class="btn btn-success" href="<?= $urlGenerator->generate('payment/add'); ?>">
          <i class="fa fa-plus"></i> <?= $translator->translate('i.new'); ?> </a>
    </div>
<?php } ?>
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
        new DataColumn(
            header:  $translator->translate('i.view'),
            visible: $canView,
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), 
                                                $urlGenerator->generate('payment/view',
                                                ['id'=>$model->getId()]),[])->render();
            }                        
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'), 
            visible: $canEdit,
            content: static function ($model) use ($s, $urlGenerator): string {
               return $model->getInv()?->getIs_read_only() === false 
                      && $s->get_setting('disable_read_only') === (string)0 
                      ? Html::a(Html::tag('i','',
                                ['class'=>'fa fa-edit fa-margin']), 
                                $urlGenerator->generate('payment/edit',
                                    ['id'=>$model->getId()]),[])->render() : '';
            }                        
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),
            visible: $canEdit,
            content: static function ($model) use ($translator, $s, $urlGenerator): string {
                return $model->getInv()?->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string)0 ? Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('payment/delete',['id'=>$model->getId()]),[]                                         
                )->render() : '';
            }                        
        ),
    ]
?>
<?= GridView::widget()
        ->columns(...$columns)
        ->dataReader($paginator)
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($header)
        ->id('w3-grid')
        ->pagination(
            $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'payment') .' '.$grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))                         
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-payment'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
