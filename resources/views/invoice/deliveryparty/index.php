<?php

declare(strict_types=1);

use App\Invoice\Entity\DeliveryParty; 
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents  
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var int $defaultPageSizeOffsetPaginator
 * @var bool $canEdit
 * @var string $alert
 * @var string $csrf
 */

?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.invoice.delivery.party'))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'deliveryparty/index'))
    ->id('btn-reset')
    ->render();

$toolbar = Div::tag();
?>
<?= Html::openTag('div');?>
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('invoice.invoice.delivery.party'); ?>
    <?= Html::closeTag('h5'); ?>
    <?= Html::openTag('div',['class' => 'btn-group']);?>
    <?php     
        if ($canEdit) {
        echo Html::a($translator->translate('invoice.invoice.delivery.party.add'),
        $urlGenerator->generate('deliveryparty/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
        );
    } ?>    
    <?= Html::closeTag('div');?>
    <?= Html::Tag('br'); ?>
    <?= Html::Tag('br'); ?>
<?= Html::closeTag('div');?>
<?= Html::openTag('div');?>
    <?= Html::Tag('br'); ?>    
<?= Html::closeTag('div');?>
<?php
    $columns = [    
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content:static fn (DeliveryParty $model) => $model->getId()
        ),   
        new DataColumn(
            'party_name',
            header: $translator->translate('i.name'),
            content:static function (DeliveryParty $model) : string
            {
                return Html::encode($model->getPartyName());
            }
        ),        
        new DataColumn(
            header: $translator->translate('i.view'), 
            content: static function (DeliveryParty $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('deliveryparty/view',['id'=>$model->getId()]),[])->render();
            }                        
        ),
        new DataColumn(
            'id',     
            header: $translator->translate('invoice.invoice.delivery.party.edit'), 
            content:static function (DeliveryParty $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('deliveryparty/edit',['id'=>$model->getId()]),[])->render(); 
            }                        
        ),  
        new DataColumn(
            header: $translator->translate('i.delete'), 
            content:static function (DeliveryParty $model) use ($translator, $urlGenerator): string {
                return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('deliveryparty/delete',['id'=>$model->getId()]),[]                                         
                )->render();
            }                        
        ),
    ]                
?>
<?= $alert; ?>
<?php
    $toolbarString =
            Form::tag()->post($urlGenerator->generate('deliveryparty/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    $grid_summary = $s->grid_summary($paginator, $translator, (int)$s->getSetting('default_list_limit'),
                  $translator->translate('invoice.invoice.delivery.party'), '');
    echo GridView::widget()
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-deliveryparty'])
        ->columns(...$columns)
        ->dataReader($paginator)        
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($header)
        ->id('w15-grid')
        ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText($translator->translate('invoice.invoice.no.records'))            
        ->toolbar($toolbarString);
?>
