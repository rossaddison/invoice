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
 * @var \App\Invoice\Entity\Merchant $merchant 
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

<?php
$columns = [    
    new DataColumn(
        'id',
        header:  $translator->translate('i.id'),
        content: static fn (object $model) => $model->getId()
    ),        
    new DataColumn(
        field: 'inv_id',
        property: 'filterInvNumber', 
        header:  $translator->translate('i.invoice'),    
        content: static function ($model) use ($urlGenerator): string {
            $return = '';
            if (null!==$model->getInv()) {
                $return = Html::a($model->getInv()->getNumber(), $urlGenerator->generate('inv/view',['id'=>$model->getInv_id()]),['style'=>'text-decoration:none'])->render(); 
            }
            return $return;
       },
       filter: true
    ),
    new DataColumn(
        'successful',    
        header:  $translator->translate('g.transaction_successful'),                
        content: static function ($model) use ($translator) : Yiisoft\Html\Tag\CustomTag {
            return $model->getSuccessful() ? Html::tag('Label',$translator->translate('i.yes'),['class'=>'btn btn-success']) : Html::tag('Label',$translator->translate('i.no'),['class'=>'btn btn-danger']);
        }
    ),            
    new DataColumn(
        'date',
        header:  $translator->translate('i.payment_date'),                
        content: static fn ($model): string => ($model->getDate())->format($datehelper->style())                        
    ),
    new DataColumn(
        field: 'driver',
        property: 'filterPaymentProvider',    
        header:  $translator->translate('g.payment_provider'),
        content: static fn ($model): string => ($model->getDriver()),
        filter: true
    ),
    new DataColumn(
        'response',    
        header:  $translator->translate('g.provider_response'),                
        content: static fn ($model): string => ($model->getResponse())                        
    ),
    new DataColumn(
        'reference',    
        header:  $translator->translate('g.transaction_reference'),                
        content: static fn ($model): string => ($model->getReference())                        
    ),
];            
?>
<?= GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])       
    ->header($gridComponents->header(' ' . $translator->translate('i.payment_logs')))
    ->id('w79-grid')
    ->pagination(
         $gridComponents->offsetPaginationWidget($defaultPageSizeOffsetPaginator, $paginator)   
    )        
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    /**
     * @see config/common/params.php `yiisoft/view` => ['parameters' => ['pageSizeLimiter' ... No need to be in payment/index
     */    
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator, 'payment').' '.$grid_summary)
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-payment-online-log'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('payment/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>