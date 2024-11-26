<?php

declare(strict_types=1);

use App\Invoice\Entity\InvItemAllowanceCharge;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\CurrentRoute $currentRoute 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $alert
 * @var string $csrf 
 * @var string $inv_item_id
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.invoice.allowance.or.charge.item'))
            )
    )
    ->render();

$toolbarReset = A::tag()
    ->addAttributes(['type' => 'reset'])
    ->addClass('btn btn-danger me-1 ajax-loader')
    ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
    ->href($urlGenerator->generate($currentRoute->getName() ?? 'invitemallowancecharge/index'))
    ->id('btn-reset')
    ->render();

$backButton = A::tag()
    ->addAttributes([
        'type' => 'reset', 
        'onclick' => 'window.history.back()',
        'class' => 'btn btn-primary me-1',
        'id' => 'btn-cancel',
    ])    
    ->content('⬅ ️'.$translator->translate('i.back'))
    ->render();

$toolbar = Div::tag();
?>
<div>
    <h5><?= $translator->translate('invoice.invoice.allowance.or.charge.item'); ?></h5>
    <div class="btn-group">
    </div>
    <br>
    <br>
</div>
<div>
<br>    
</div>
<?php 
    $add = $translator->translate('invoice.invoice.allowance.or.charge.item.add');
    $url = $urlGenerator->generate('invitemallowancecharge/add',['inv_item_id' => $inv_item_id]);
?>

<?= Html::i(Html::a('  '.$add, $url,['class'=>'btn btn-primary',
'style'=>'font-family:Arial']),['class'=>'btn btn-primary fa fa-item']); ?>
<br>
<br>
<?php
    $columns = [
        new DataColumn(
            'id',
            header:  $translator->translate('i.id'),
            content: static fn (InvItemAllowanceCharge $model) => $model->getId()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason.code'),
            content: static fn (InvItemAllowanceCharge $model) => $model->getAllowanceCharge()?->getReasonCode() ?? ''
        ),
        new DataColumn(
            content: static function (InvItemAllowanceCharge $model) use ($translator) : string 
            {
                if ($model->getAllowanceCharge()?->getIdentifier() == 1) {
                    return  $translator->translate('invoice.invoice.allowance.or.charge.charge');
                } else {
                   return $translator->translate('invoice.invoice.allowance.or.charge.allowance');  
                } 
            },
        ),    
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason'),
            content: static fn (InvItemAllowanceCharge $model) => $model->getAllowanceCharge()?->getReason() ?? ''
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.amount'),
            content: static function (InvItemAllowanceCharge $model) use ($numberHelper) : string {
                // show the charge in brackets
                if ($model->getAllowanceCharge()?->getIdentifier() == 0) {
                    return '('.$numberHelper->format_currency($model->getAmount()).')';
                } else {
                    return $numberHelper->format_currency($model->getAmount());
                }
            }
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.vat'),
            content: static fn (InvItemAllowanceCharge $model) => $numberHelper->format_currency($model->getVat())
        ),        
        new DataColumn(
            header:  $translator->translate('i.edit'), 
            content: static function (InvItemAllowanceCharge $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-pencil fa-margin']), $urlGenerator->generate('invitemallowancecharge/edit',['id'=>$model->getId()]),[])->render();
            }                        
        ),
        new DataColumn(
            header:  $translator->translate('i.view'), 
            content: static function (InvItemAllowanceCharge $model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('invitemallowancecharge/view',['id'=>$model->getId()]),[])->render();
            }                           
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'), 
            content: static function (InvItemAllowanceCharge $model) use ($translator, $urlGenerator): string {
                return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('invitemallowancecharge/delete',['id'=>$model->getId()]),[]                                         
                )->render();
            }                        
        ),
    ];
?>
<?php
    $grid_summary =  $s->grid_summary(
        $paginator, $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.invoice.allowance.or.charge.item'), 
    '');
    $toolbarString = 
            Form::tag()->post($urlGenerator->generate('invitemallowancecharge/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Div::tag()->addClass('float-end m-3')->content($backButton)->encode(false)->render() .
            Form::tag()->close();
    echo GridView::widget()
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invitemallowancecharge'])
        ->columns(...$columns)        
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($header)
        ->id('w18-grid')
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