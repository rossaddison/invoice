<?php

declare(strict_types=1);

use App\Invoice\Entity\UnitPeppol;
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

?>
<?php

/**
 * @var App\Invoice\Entity\UnitPeppol $unitpeppol
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator 
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
                            ->content(' ' . Html::encode($translator->translate('invoice.unit.peppol.index')))
                )
        )
        ->render();
    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'unitpeppol/index'))
        ->id('btn-reset')
        ->render();
    $toolbar = Div::tag();
?>
<div>
    <h5><?= $translator->translate('invoice.unit.peppol.add'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('unitpeppol/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($translator->translate('i.new')); ?>
        </a>
    </div>
</div>
<br>
    <?php
        $columns = [
            new DataColumn(
                'id',
                header: $translator->translate('i.id'),
                content: static fn (UnitPeppol $model) => Html::encode($model->getId())
            ),
            new DataColumn(
                'unit_id',
                header:  $translator->translate('i.unit_name'),
                content: static fn (UnitPeppol $model) => Html::encode($model->getUnit()?->getUnit_name())
            ),
            new DataColumn(
                'unit_id',
                header:  $translator->translate('i.unit_name_plrl'),    
                content: static fn (UnitPeppol $model) => Html::encode($model->getUnit()?->getUnit_name_plrl())
            ),
            new DataColumn(
                'code',
                header:  $translator->translate('i.code'),
                content: static fn (UnitPeppol $model) => Html::encode($model->getCode())
            ),
            new DataColumn(
                'name',
                header:  $translator->translate('i.name'),
                content: static fn (UnitPeppol $model) => Html::encode($model->getName())
            ),
            new DataColumn(
                'description',
                header:  $translator->translate('i.description'),
                content: static fn (UnitPeppol $model) => Html::encode($model->getDescription())
            ),
            new DataColumn(
                header:  $translator->translate('i.view'),    
                content: static function (UnitPeppol $model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('unitpeppol/view',['id'=>$model->getId()]),[])->render();
                }
            ),
            new DataColumn(
                header:  $translator->translate('i.edit'),    
                content: static function (UnitPeppol $model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('unitpeppol/edit',['id'=>$model->getId()]),[])->render();
                }
            ),
            new DataColumn(
                header:  $translator->translate('i.delete'),    
                content: static function (UnitPeppol $model) use ($translator, $urlGenerator): string {
                   return Html::a( Html::tag('button',
                            Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                            [
                                'type'=>'submit', 
                                'class'=>'dropdown-button',
                                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                            ]
                            ),
                            $urlGenerator->generate('unitpeppol/delete',['id'=>$model->getId()]),[]                                         
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
            $translator->translate('invoice.unit.peppol'),
            ''
        );
        $toolbarString = Form::tag()->post($urlGenerator->generate('unitpeppol/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
        echo GridView::widget()
        ->columns(...$columns)
        ->dataReader($paginator)
        ->tableAttributes(['class' => 'table table-striped text-center h-81','id'=>'table-unitpeppol'])        
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($header)
        ->id('w44-grid')
        ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText($translator->translate('invoice.invoice.no.records'))       
        ->toolbar($toolbarString);
    ?>
