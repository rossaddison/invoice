<?php
declare(strict_types=1);

use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Html\Html;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\View\WebView;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Yii\DataView\UrlConfig;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

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
                            ->content(' ' . Html::encode($translator->translate('i.product')))
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
    
    // Trigger $(document).on('click', '#product_filters_submit', function () located in C:\wamp64\www\invoice\src\Invoice\Asset\rebuild-1.13\js\product.js
    // which in turn runs the ProductController.php index_filters function which returns the index view with the productReppositories search
    $toolbarFilter = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('product_filters_submit')    
        ->addClass('btn btn-info me-1')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href('#product_filters_submit')
        ->id('product_filters_submit')
        ->render();

    $toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('i.products'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('product/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($translator->translate('i.new')); ?>
        </a>
    </div>
</div>
<br>
<div>

</div>
<div>
<?php 
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => Html::encode($model->getProduct_id()),
            withSorting: true,    
        ),        
        new DataColumn(
            'family_id',
            header: $translator->translate('i.family'),                
            content: static fn ($model): string => Html::encode($model->getFamily()->getFamily_name())                  
        ),
        new DataColumn(
            /**
             * Use: Full parameter input example of DataColumn
             * @see Yiisoft\Yii\DataView\Column\DataColumn bool|array|FilterWidget|DropdownFilter|TextInputFilter
             */    
            'product_sku',
            queryProperty: 'filter_product_sku',
            header:  $translator->translate('i.product_sku'),
            encodeHeader: true,
            content: static fn ($model): string => Html::encode($model->getProduct_sku()),
            // bool|array   bool => TextInputFilter e.g. filter: true; array => DropDownFilter e.g. as below     
            filter: $optionsDataProductsDropdownFilter,
            visible: true
        ),
        new DataColumn(
            'product_description',    
            header: $translator->translate('i.product_description'),                
            content: static fn ($model): string => Html::encode(ucfirst($model->getProduct_description())) 
        ),
        new DataColumn(
            'product_price',
            queryProperty: 'filter_product_price',    
            header: $translator->translate('i.product_price'). ' ( '. $s->get_setting('currency_symbol'). ' ) ',   
            content: static fn ($model): string => Html::encode($model->getProduct_price()),
            filter: true    
        ),
        new DataColumn(
            'product_price_base_quantity',    
            header: $translator->translate('invoice.product.price.base.quantity'),
            content: static fn ($model): string => Html::encode($model->getProduct_price_base_quantity())                        
        ),
        new DataColumn(
            'product_unit',     
            header: $translator->translate('i.product_unit'),                
            content: static fn ($model): string => Html::encode((ucfirst($model->getUnit()->getUnit_name())))                        
        ),
        new DataColumn(
            'tax_rate_id',    
            header: $translator->translate('i.tax_rate'),
            content: static fn ($model): string => ($model->getTaxrate()->getTax_rate_id()) ? Html::encode($model->getTaxrate()->getTax_rate_name()) : $translator->translate('i.none')                       
        ),
        new DataColumn(
            'product_tariff',                    
            header: $s->get_setting('sumex') ? $translator->translate('i.product_tariff'). '('. $s->get_setting('currency_symbol'). ')' : '',                
            content: static fn ($model): string => ($s->get_setting('sumex') ? Html::encode($model->getProduct_tariff()) : Html::encode($translator->translate('i.none'))),                       
            visible: $s->get_setting('sumex') ? true : false
        ),
        new DataColumn(
            header: $translator->translate('invoice.product.property.add'),    
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(
                       Html::tag('i','',['class'=>'fa fa-plus fa-margin dropdown-button text-decoration-none']), 
                       $urlGenerator->generate('productproperty/add',['product_id'=>$model->getProduct_id()]),[])->render();
            },
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('/invoice/product/view/'. $model->getProduct_id())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('/invoice/product/edit/'. $model->getProduct_id())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('/invoice/product/delete/'. $model->getProduct_id())
            ->render(),
        )
    ];       
?>
<?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->enableMultisort(true)
    ->urlQueryParameters(['filter_product_sku', 'filter_product_price'])            
    ->header($header)
    ->id('w4-grid')
    ->pagination(
    OffsetPagination::widget()
        /**
         * @link https://getbootstrap.com/docs/5.0/components/pagination/
         */    
        ->listTag('ul')    
        ->listAttributes(['class' => 'pagination'])
        ->itemTag('li')
        ->itemAttributes(['class' => 'page-item'])
        ->linkAttributes(['class' => 'page-link'])
        ->currentItemClass('active')
        ->currentLinkClass('page-link')
        ->disabledItemClass('disabled')
        ->disabledLinkClass('disabled')
        ->defaultPageSize($defaultPageSizeOffsetPaginator)
        ->urlConfig(new UrlConfig()) 
        ->urlCreator(new UrlCreator($urlGenerator))        
        ->paginator($paginator)
        ->render()
    )
    ->rowAttributes(['class' => 'align-middle'])
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($pageSizeLimiter::buttons($currentRoute, $s, $urlGenerator).' '.$grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-product'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('product/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarFilter)->encode(false)->render() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>
</div>

