<?php

declare(strict_types=1);

use App\Invoice\Entity\ClientNote;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\Pagination\OffsetPagination;

/**
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
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
                            ->content(' ' . Html::encode($translator->translate('invoice.client.note')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'clientnote/index'))
        ->id('btn-reset')
        ->render();
    
    $toolbar = Div::tag();
?>

<div>
    <h5><?= $translator->translate('invoice.client.note'); ?></h5>
    <div class="btn-group">
        <a class="btn btn-success" href="<?= $urlGenerator->generate('clientnote/add'); ?>">
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
            content: static fn (ClientNote $model) => Html::encode($model->getId())
        ),        
        new DataColumn(
            'client_id',
            header: $translator->translate('i.client'),                
            content: static fn (ClientNote $model): string => Html::encode(($model->getClient()?->getClient_name() ?? '#') . ' '.($model->getClient()?->getClient_surname() ?? '#'))                  
        ),
        new DataColumn(
            'note',    
            header: $translator->translate('invoice.client.note'),                
            content: static fn (ClientNote $model): string => Html::encode(ucfirst($model->getNote())) 
        ),
        new DataColumn(
            'date_note',    
            header: $translator->translate('invoice.client.note.date'),                
            content: static fn (ClientNote $model): string => Html::encode((!is_string($dateNote = $model->getDate_note()) ? $dateNote->format($dateHelper->style()) : '')) 
        ),
        new ActionColumn(
            content: static fn(ClientNote $model): string => null!==($modelId = $model->getId()) ? 
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.view')])
            ->content('🔎')
            ->encode(false)
            ->href('clientnote/view/'. $modelId)
            ->render() : '',
        ),
        new ActionColumn(
            content: static fn(ClientNote $model): string => null!==($modelId = $model->getId()) ?
            Html::a()
            ->addAttributes(['class' => 'dropdown-button text-decoration-none', 'title' => $translator->translate('i.edit')])
            ->content('✎')
            ->encode(false)
            ->href('clientnote/edit/'. $modelId)
            ->render() : '',
        ),
        new ActionColumn(
            content: static fn(ClientNote $model): string => null!==($modelId = $model->getId()) ? 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('clientnote/delete/'. $modelId)
            ->render() : '',
        )
    ];       
?>
<?php
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.client.notes'), 
        ''
    );
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('clientnote/index'))->csrf($csrf)->open() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-clientnote'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w44-grid')
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
</div>

