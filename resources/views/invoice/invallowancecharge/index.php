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
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;
use Yiisoft\Router\CurrentRoute;

/**
 * @var \App\Invoice\Entity\InvAllowanceCharge $invallowancecharge 
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
                I::tag()->addClass('bi bi-receipt')->content(' ' . $translator->translate('invoice.invoice.allowance.or.charge.inv'))
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
    <h5><?= $translator->translate('invoice.invoice.allowance.or.charge.inv'); ?></h5>
    <div class="btn-group">
    </div>
    <br>
    <br>
</div>
<div>
<br>    
</div>
<?php
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (object $model) => $model->getId()
        ),
        new DataColumn(
            'inv_id',
            header: $translator->translate('i.invoice'),    
            content: static function ($model) use ($urlGenerator): string {
                return Html::a($model->getInv()?->getNumber(), $urlGenerator->generate('inv/view', ['id'=>$model->getInv_id()]),[])->render();
            }
        ),       
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason.code'),
            content: static fn (object $model) => $model->getAllowanceCharge()->getReason_code()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.reason'),
            content: static fn (object $model) => $model->getAllowanceCharge()->getReason()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.allowance.or.charge.amount'),
            content: static fn (object $model) => $model->getAmount()
        ),        
        new DataColumn(
            header:  $translator->translate('invoice.invoice.vat'),
            content: static fn (object $model) => $model->getVat()
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('/invoice/invallowancecharge/view/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('/invoice/invallowancecharge/edit/'. $model->getId())
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
            ->href('/invoice/invallowancecharge/delete/'. $model->getId())
            ->render(),
        )        
    ];            
?>
<?= GridView::widget()
    ->columns(...$columns)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    //->filterPosition('header')
    //->filterModelName('invallowancecharge')
    ->header($header)
    ->id('w3-grid')
    ->dataReader($paginator)
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
        Form::tag()->post($urlGenerator->generate('invallowancecharge/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>
