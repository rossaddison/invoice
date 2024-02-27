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
 * @var \App\Invoice\Entity\AllowanceCharge $allowancecharge 
 * @var string $csrf
 * @var CurrentRoute $currentRoute 
 * @var OffsetPaginator $paginator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var TranslatorInterface $translator
 * @var WebView $this
 */
?>
<?php
$header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.invoice.allowance.or.charge'))
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
<?= Html::openTag('div');?>
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('invoice.invoice.allowance.or.charge'); ?>
    <?= Html::closeTag('h5'); ?>
    <?= Html::openTag('div',['class' => 'btn-group']);?>
    <?php     
        if ($canEdit) {
        echo Html::a('Add Allowance',
        $urlGenerator->generate('allowancecharge/add_allowance'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
        );
        echo Html::a('Add Charge',
        $urlGenerator->generate('allowancecharge/add_charge'),
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
            content:static fn (object $model) => $model->getId()
        ),        
        new DataColumn(
            header: $translator->translate('i.view'), 
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('allowancecharge/view',['id'=>$model->getId()]),[])->render();
            }                        
        ),
        new DataColumn(
            'identifier',     
            header: $translator->translate('invoice.invoice.allowance.or.charge.edit.allowance'), 
            content:static function ($model) use ($urlGenerator): string {
               return !$model->getIdentifier() ? Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('allowancecharge/edit_allowance',['id'=>$model->getId()]),[])->render() : ''; 
            }                        
        ),
        new DataColumn(
            'identifier',    
            header: $translator->translate('invoice.invoice.allowance.or.charge.edit.charge'), 
            content:static function ($model) use ($urlGenerator): string {
               return $model->getIdentifier() ? Html::a(Html::tag('i','',['class'=>'fa fa-edit fa-margin']), $urlGenerator->generate('allowancecharge/edit_charge',['id'=>$model->getId()]),[])->render() : ''; 
            }                        
        ),        
        new DataColumn(
            header: $translator->translate('i.delete'), 
            content:static function ($model) use ($translator, $urlGenerator): string {
                return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('allowancecharge/delete',['id'=>$model->getId()]),[]                                         
                )->render();
            }                        
        ),
    ]                
?>
<?= $alert; ?>
<?= GridView::widget()
        ->columns(...$columns)
        ->dataReader($paginator)        
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        //->filterPosition('header')
        //->filterModelName('allowancecharge')
        ->header($header)
        ->id('w3-grid')
        ->pagination(
        OffsetPagination::widget()
             ->paginator($paginator) 
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))            
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-allowancecharge'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('allowancecharge/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>
