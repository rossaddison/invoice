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
 * @var \App\Invoice\Entity\Product $product
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
    $header = Div::tag()
        ->addClass('row')
        ->content(
            H5::tag()
                ->addClass('bg-primary text-white p-3 rounded-top')
                ->content(
                    I::tag()->addClass('bi bi-receipt')
                            ->content(' ' . Html::encode($s->trans('product')))
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


<div>
    <h5><?= $s->trans('products'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('product/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($s->trans('new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
    <?= GridView::widget()
        ->columns(
            DataColumn::create()
                ->attribute('id')
                ->label($s->trans('id'))
                ->value(static fn (object $model) => Html::encode($model->getProduct_id())
            ),        
            DataColumn::create()
                ->label($s->trans('family'))                
                ->attribute('family_id')     
                ->value(static fn ($model): string => Html::encode($model->getFamily()->getFamily_name())                        
            ),
            DataColumn::create()
                ->label($s->trans('product_sku'))                
                ->attribute('product_sku')     
                ->value(static fn ($model): string => Html::encode($model->getProduct_sku())                        
            ),
            DataColumn::create()
                ->label($s->trans('product_description'))                
                ->attribute('product_description')     
                ->value(static fn ($model): string => Html::encode(ucfirst($model->getProduct_description()))                        
            ),
            DataColumn::create()
                ->label($s->trans('product_price'))                
                ->attribute('product_price')     
                ->value(static fn ($model): string => Html::encode($s->format_currency($model->getProduct_price()))                        
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.product.price.base.quantity'))                
                ->attribute('product_price_base_quantity')     
                ->value(static fn ($model): string => Html::encode($model->getProduct_price_base_quantity())                        
            ),
            DataColumn::create()
                ->label($s->trans('product_unit'))                
                ->attribute('product_unit')     
                ->value(static fn ($model): string => Html::encode((ucfirst($model->getUnit()->getUnit_name())))                        
            ),
            DataColumn::create()
                ->label($s->trans('tax_rate'))                
                ->attribute('tax_rate_id')     
                ->value(static fn ($model): string => ($model->getTaxrate()->getTax_rate_id()) ? Html::encode($model->getTaxrate()->getTax_rate_name()) : $s->trans('none')                       
            ),
            DataColumn::create()
                ->visible($s->get_setting('sumex') ? true : false)
                ->label($s->get_setting('sumex') ? $s->trans('product_tariff') : '')                
                ->attribute('product_tariff')     
                ->value(static fn ($model): string => ($s->get_setting('sumex') ? Html::encode($model->getProduct_tariff()) : Html::encode($s->trans('none')))                       
            ),
            DataColumn::create()
                ->label($translator->translate('invoice.product.property.add'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-plus fa-margin']), $urlGenerator->generate('productproperty/add',['product_id'=>$model->getProduct_id()]),[])->render();
                }
            ),
            DataColumn::create()
                ->label($s->trans('view'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('product/view',['id'=>$model->getProduct_id()]),[])->render();
                }
            ),
            DataColumn::create()
                ->label($s->trans('edit'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('product/edit',['id'=>$model->getProduct_id()]),[])->render();
                }
            ),
            DataColumn::create()
                ->label($s->trans('delete'))    
                ->value(static function ($model) use ($s, $urlGenerator): string {
                   return Html::a( Html::tag('button',
                            Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                            [
                                'type'=>'submit', 
                                'class'=>'dropdown-button',
                                'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
                            ]
                            ),
                            $urlGenerator->generate('product/delete',['id'=>$model->getProduct_id()]),[]                                         
                        )->render();
                }
            ),          
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('product')
        ->header($header)
        ->id('w4-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator)
             ->urlArguments([])
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summary($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-product'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('product/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
    </div>

