<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Data\Paginator\OffsetPaginator;
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

?>
<?php

/**
 * @var \App\Invoice\Entity\UnitPeppol $unitpeppol
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
                            ->content(' ' . Html::encode($translator->translate('invoice.unit.peppol.index')))
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
    <h5><?= $translator->translate('invoice.unit.peppol.add'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('unitpeppol/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($s->trans('new')); ?>
        </a>
    </div>
</div>
<br>
    <?= GridView::widget()
        ->columns(
            DataColumn::create()
                ->attribute('id')
                ->label($s->trans('id'))
                ->value(static fn (object $model) => Html::encode($model->getId())
            ),
            DataColumn::create()
                ->label($s->trans('unit_name'))    
                ->attribute('unit_id')
                ->value(static fn (object $model) => Html::encode($model->getUnit()->getUnit_name())
            ),
            DataColumn::create()
                ->label($s->trans('unit_name_plrl'))    
                ->attribute('unit_id')
                ->value(static fn (object $model) => Html::encode($model->getUnit()->getUnit_name_plrl())
            ),
            DataColumn::create()
                ->attribute('code')
                ->label($s->trans('code'))
                ->value(static fn (object $model) => Html::encode($model->getCode())
            ),
            DataColumn::create()
                ->attribute('name')
                ->label($s->trans('name'))
                ->value(static fn (object $model) => Html::encode($model->getName())
            ),
            DataColumn::create()
                ->attribute('description')
                ->label($s->trans('description'))
                ->value(static fn (object $model) => Html::encode($model->getDescription())
            ),
            DataColumn::create()
                ->label($s->trans('view'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('unitpeppol/view',['id'=>$model->getId()]),[])->render();
                }
            ),
            DataColumn::create()
                ->label($s->trans('edit'))    
                ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('unitpeppol/edit',['id'=>$model->getId()]),[])->render();
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
                            $urlGenerator->generate('unitpeppol/delete',['id'=>$model->getId()]),[]                                         
                        )->render();
                }
            ),          
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('unitpeppol')
        ->header($header)
        ->id('w44-grid')
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
        ->tableAttributes(['class' => 'table table-striped text-center h-81','id'=>'table-unitpeppol'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('unitpeppol/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
