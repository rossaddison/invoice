<?php

declare(strict_types=1);

use App\Invoice\Entity\Family;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionButton;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var App\Widget\PageSizeLimiter $pageSizeLimiter
 * @var Yiisoft\Router\CurrentRoute $currentRoute 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var string $alert
 * @var string $csrf 
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
                            ->content(' ' . Html::encode($translator->translate('i.family')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'family/index'))
        ->id('btn-reset')
        ->render();
    
    $toolbarFilter = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('family_filters_submit')    
        ->addClass('btn btn-info me-1')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href('#family_filters_submit')
        ->id('family_filters_submit')
        ->render();

    $toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('i.families'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('family/add'); ?>">
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
            content: static fn (Family $model) => Html::encode($model->getFamily_id()),
            withSorting: true,    
        ),        
        new DataColumn(
            'id',
            header: $translator->translate('i.family'),                
            content: static fn (Family $model): string => Html::encode($model->getFamily_name() ?? '')                  
        ),
        new ActionColumn(buttons: [
            new ActionButton(
                content: '🔎',
                url: static function(Family $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('family/view', ['id' => $model->getFamily_id()]);     
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.view'),
                ]      
            ),
            new ActionButton(
                content: '✎',
                url: static function(Family $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('family/edit', ['id' => $model->getFamily_id()]);     
                },
                attributes: [
                    'data-bs-toggle' => 'tooltip',
                    'title' => $translator->translate('i.edit'),
                ]      
            ),
            new ActionButton(
                content: '❌',
                url: static function(Family $model) use ($urlGenerator) : string {
                     return $urlGenerator->generate('family/delete', ['id' => $model->getFamily_id()]);     
                },
                attributes: [
                    'title' => $translator->translate('i.delete'),
                    'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                ]      
            ),          
        ])
    ];       
?>
<?php
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('i.families'),
        ''
    );
    $toolbarString = Form::tag()->post($urlGenerator->generate('product/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarFilter)->encode(false)->render() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-product'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->multiSort(true) 
    ->urlQueryParameters(['filter_product_sku', 'filter_product_price'])            
    ->header($header)
    ->id('w4-grid')
    ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText($translator->translate('invoice.invoice.no.records'))
    ->toolbar($toolbarString);
?>
</div>

