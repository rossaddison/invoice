<?php
declare(strict_types=1);

use App\Invoice\Helpers\NumberHelper;
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
 * @var \App\Invoice\Entity\InvItemAllowanceCharge $acii 
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
$numberHelper = new NumberHelper($s);
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
    ->href($urlGenerator->generate($currentRoute->getName()))
    ->id('btn-reset')
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
    $url = $urlGenerator->generate('acii/add',['inv_item_id' => $inv_item_id]);
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
            content: static fn (object $model) => $model->getId()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason.code'),
            content: static fn (object $model) => $model->getAllowanceCharge()->getReason_code()
        ),
        new DataColumn(
            content: static function ($model) use ($translator) : string 
            {
                if ($model->getAllowanceCharge()->getIdentifier() == 1) {
                    return  $translator->translate('invoice.invoice.allowance.or.charge.charge');
                } else {
                   return $translator->translate('invoice.invoice.allowance.or.charge.allowance');  
                } 
            },
        ),    
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason'),
            content: static fn (object $model) => $model->getAllowanceCharge()->getReason()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.amount'),
            content: static function (object $model) use ($numberHelper) : string {
                // show the charge in brackets
                if ($model->getAllowanceCharge()->getIdentifier() == 0) {
                    return '('.$numberHelper->format_currency($model->getAmount()).')';
                } else {
                    return $numberHelper->format_currency($model->getAmount());
                }
            }
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.vat'),
            content: static fn (object $model) => $numberHelper->format_currency($model->getVat())
        ),        
        new DataColumn(
            header:  $translator->translate('i.edit'), 
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-pencil fa-margin']), $urlGenerator->generate('acii/edit',['id'=>$model->getId()]),[])->render();
            }                        
        ),
        new DataColumn(
            header:  $translator->translate('i.view'), 
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('acii/view',['id'=>$model->getId()]),[])->render();
            }                           
        ),
        new DataColumn(
            header:  $translator->translate('i.delete'), 
            content: static function ($model) use ($translator, $urlGenerator): string {
                return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
                    ]
                    ),
                    $urlGenerator->generate('acii/delete',['id'=>$model->getId()]),[]                                         
                )->render();
            }                        
        ),
    ];
?>
<?= GridView::widget()
        ->rowAttributes(['class' => 'align-middle'])
        ->columns(...$columns)        
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        //->filterPosition('header')
        //->filterModelName('acii')
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
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))            
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invitemallowancecharge'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('acii/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>