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
 * @var \App\Invoice\Entity\ClientNote $clientnote
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
                            ->content(' ' . Html::encode($translator->translate('invoice.client.note')))
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
    <h5><?= $translator->translate('invoice.client.note'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('clientnote/add'); ?>">
            <i class="fa fa-plus"></i> <?= Html::encode($s->trans('new')); ?>
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
            header: $s->trans('id'),
            content: static fn (object $model) => Html::encode($model->getId())
        ),        
        new DataColumn(
            'client_id',
            header: $s->trans('client'),                
            content: static fn ($model): string => Html::encode($model->getClient()->getClient_name() . ' '.$model->getClient()->getClient_surname())                  
        ),
        new DataColumn(
            'note',    
            header: $translator->translate('invoice.client.note'),                
            content: static fn ($model): string => Html::encode(ucfirst($model->getNote())) 
        ),
        new DataColumn(
            'date',    
            header: $translator->translate('invoice.client.note.date'),                
            content: static fn ($model): string => Html::encode(($model->getDate()->format($datehelper->style()))) 
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $s->trans('view')])
            ->content('🔎')
            ->encode(false)
            ->href('/invoice/clientnote/view/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $s->trans('edit')])
            ->content('✎')
            ->encode(false)
            ->href('/invoice/clientnote/edit/'. $model->getId())
            ->render(),
        ),
        new ActionColumn(
            content: static fn($model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $s->trans('delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$s->trans('delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('/invoice/clientnote/delete/'. $model->getId())
            ->render(),
        )
    ];       
?>
<?= GridView::widget()
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('clientnote')            
    ->header($header)
    ->id('w44-grid')
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
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-clientnote'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('clientnote/index'))->csrf($csrf)->open() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>
</div>

