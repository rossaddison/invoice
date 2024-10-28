<?php
declare(strict_types=1);

use App\Invoice\Entity\ProductImage;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var string $alert
 * @var string $csrf
 */ 

echo $alert;

?>
<h1><?= $translator->translate('invoice.productimage.index'); ?></h1>
<div>
</div>
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
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'productimage/index'))
        ->id('btn-reset')
        ->render();
    $toolbar = Div::tag();
?>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (ProductImage $model) => Html::encode($model->getId())
        ),
        new DataColumn(
            'product_id',
            header:  $translator->translate('i.product'),
            content: static fn (ProductImage $model): string => Html::encode($model->getProduct()?->getProduct_name() ?? '')                        
        ),
        new DataColumn(
            'file_name_original',     
            header:  $translator->translate('invoice.upload.filename.original'),                
            content: static fn (ProductImage $model): string => Html::encode($model->getFile_name_original())                        
        ),
        new DataColumn(
            'file_name_new',     
            header:  $translator->translate('invoice.upload.filename.new'),               
            content: static fn (ProductImage $model): string => Html::encode($model->getFile_name_new())                        
        ),
        new DataColumn(
            'description',
            header:  $translator->translate('invoice.upload.filename.description'),                
            content: static fn (ProductImage $model): string => Html::encode($model->getDescription())                        
        ),
        new DataColumn(
            header:  $translator->translate('i.view'),    
            content: static function (ProductImage $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('productimage/view',['id'=>$model->getId()]),[])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.edit'),    
            content: static function (ProductImage $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('productimage/edit',['id'=>$model->getId()]),[])->render();
            }
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'),    
            content: static function (ProductImage $model) use ($translator, $urlGenerator): string {
               return Html::a( Html::tag('button',
                        Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                        [
                            'type'=>'submit', 
                            'class'=>'dropdown-button',
                            'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                        ]
                        ),
                        $urlGenerator->generate('productimage/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
            }
        ),
    ];            
?>
<?php 
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.product.image.plural'),
        ''
    );
    $toolbarString = Form::tag()->post($urlGenerator->generate('upload/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-125','id'=>'table-upload'])
    ->columns(...$columns)
    ->dataReader($paginator)    
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->id('w44-grid')
    ->pagination(
        OffsetPagination::widget()
             ->paginator($paginator)
             ->render(),
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);            
?>