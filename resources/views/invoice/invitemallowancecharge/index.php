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
<?= GridView::widget()
        ->columns(
            DataColumn::create()
            ->attribute('id')
            ->label($s->trans('id'))
            ->value(static fn (object $model) => $model->getId()),        
            DataColumn::create()
            ->label($translator->translate('invoice.invoice.allowance.or.charge.reason.code'))
            ->value(static fn (object $model) => $model->getAllowanceCharge()->getReason_code()),        
            DataColumn::create()
            ->label($translator->translate('invoice.invoice.allowance.or.charge.reason'))
            ->value(static fn (object $model) => $model->getAllowanceCharge()->getReason()),        
            DataColumn::create()
            ->label($translator->translate('invoice.invoice.allowance.or.charge.amount'))
            ->value(static fn (object $model) => $numberHelper->format_currency($model->getAmount())),        
            DataColumn::create()
            ->label($translator->translate('invoice.invoice.vat'))
            ->value(static fn (object $model) => $numberHelper->format_currency($model->getVat())),        
            DataColumn::create()
            ->label($s->trans('edit')) 
            ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-pencil fa-margin']), $urlGenerator->generate('acii/edit',['id'=>$model->getId()]),[])->render();
            }                        
            ),
            DataColumn::create()
            ->label($s->trans('view')) 
            ->value(static function ($model) use ($urlGenerator): string {
                   return Html::a(Html::tag('i','',['class'=>'fa fa-eye fa-margin']), $urlGenerator->generate('acii/view',['id'=>$model->getId()]),[])->render();
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
                        $urlGenerator->generate('acii/delete',['id'=>$model->getId()]),[]                                         
                    )->render();
                }                        
            ),                       
        )
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->filterPosition('header')
        ->filterModelName('acii')
        ->header($header)
        ->id('w18-grid')
        ->paginator($paginator)
        ->pagination(
        OffsetPagination::widget()
             ->menuClass('pagination justify-content-center')
             ->paginator($paginator) 
             ->render(),
        )
        ->rowAttributes(['class' => 'align-middle'])
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summary($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText((string)$translator->translate('invoice.invoice.no.records'))            
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-invitemallowancecharge'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('acii/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
?>