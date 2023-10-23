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
use Yiisoft\Yii\DataView\OffsetPagination;
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $s->trans('payment'))
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
     <h5><?= $s->trans('payment'); ?></h5>
     <a class="btn btn-success" href="<?= $urlGenerator->generate('payment/add'); ?>">
          <i class="fa fa-plus"></i> <?= $s->trans('new'); ?> </a>
    </div>
<?php } ?>
<br>
<?= GridView::widget()
        ->columns(
            DataColumn::create()
                ->label($s->trans('id'))                
                ->attribute('id')     
                ->value(static fn ($model): string => $model->getId()                        
            ),    
            DataColumn::create()
                ->label($s->trans('payment_date'))                
                ->attribute('payment_date')     
                ->value(static fn ($model): string => ($model->getPayment_date())->format($datehelper->style())                        
            ),
            DataColumn::create()
                ->label($s->trans('amount'))                
                ->attribute('amount')     
                ->value(static function ($model) use ($s): string|null {                        
                    return $s->format_currency($model->getAmount() ?: 0.00);
                }
            ),
            DataColumn::create()
                ->label($s->trans('note'))                
                ->attribute('note')     
                ->value(static fn ($model): string => $model->getNote()                        
            ),       
            DataColumn::create()
                ->label($s->trans('invoice'))
                ->attribute('inv_id')
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a($model->getInv()?->getNumber() ?? '', $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),['style'=>'text-decoration:none'])->render();
               }                       
            ), 
            DataColumn::create()
                ->label($s->trans('total'))                
                ->attribute('inv_id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getTotal() : 0.00);
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('paid'))                
                ->attribute('inv_id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getPaid() : 0.00);
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('balance'))                
                ->attribute('id')     
                ->value(static function ($model) use ($s, $iaR) : string|null {
                   $inv_amount = (($iaR->repoInvAmountCount((int)$model->getInv_id()) > 0) ? $iaR->repoInvquery((int)$model->getInv_id()) : null);
                   return $s->format_currency(null!==$inv_amount ? $inv_amount->getBalance() : 0.00);
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('payment_method'))                
                ->attribute('payment_method_id')     
                ->value(static function ($model) : string|null {
                   return $model->getPaymentMethod()->getId() ? $model->getPaymentMethod()->getName() : '';
                }                        
            ),        
            DataColumn::create()
                ->label($s->trans('view')) 
                ->visible($canView)
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),[])->render();
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('edit')) 
                ->visible($canEdit)
                ->value(static function ($model) use ($s, $urlGenerator): string {
                   return $model->getInv()?->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string)0 ? Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('inv/edit',['id'=>$model->getInv_id()]),[])->render() : '';
                }                        
            ),
            DataColumn::create()
                ->label($s->trans('delete'))
                ->visible($canEdit)
                ->value(static function ($model) use ($s, $urlGenerator): string {
                    return $model->getInv()?->getIs_read_only() === false && $s->get_setting('disable_read_only') === (string)0 ? Html::a( Html::tag('button',
                        Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                        [
                            'type'=>'submit', 
                            'class'=>'dropdown-button',
                            'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                        ]
                        ),
                        $urlGenerator->generate('inv/delete',['id'=>$model->getInv_id()]),[]                                         
                    )->render() : '';
                }                        
            ),                                 
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('payment')
        ->header($header)
        ->id('w3-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             ->urlArguments([])
             // No need to use page argument since built-in.    
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summary($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))                         
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-payment'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
