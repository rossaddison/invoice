<?php

declare(strict_types=1);

use App\Invoice\Entity\GentorRelation;
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
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Router\CurrentRoute $currentRoute 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
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
                            ->content(' ' . Html::encode($translator->translate('invoice.generator.relations')))
                )
        )
        ->render();

    $toolbarReset = A::tag()
        ->addAttributes(['type' => 'reset'])
        ->addClass('btn btn-danger me-1 ajax-loader')
        ->content(I::tag()->addClass('bi bi-bootstrap-reboot'))
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'gentorrelation/index'))
        ->id('btn-reset')
        ->render();
    
    $toolbar = Div::tag();
?>
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?><?= $translator->translate('invoice.generator.relations'); ?><?= Html::closeTag('h5'); ?>
        <?= Html::openTag('div'); ?>
            <?= Html::a(I::tag()->addClass('bi bi-plus')->content(' '.Html::encode($translator->translate('i.new'))), 
                    $urlGenerator->generate('generatorrelation/add'),
                    ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::br(); ?>
    <?= Html::openTag('div'); ?>

<?php 
    $columns = [
        new DataColumn(
            'id',
            header: $translator->translate('i.id'),
            content: static fn (GentorRelation $model) => Html::encode($model->getRelation_id())
        ),
        new DataColumn(
            'lowercasename',
            header: $translator->translate('invoice.generator.relation.form.lowercase.name'),
            content: static fn (GentorRelation $model) => Html::encode($model->getLowercase_name())
        ),
        new DataColumn(
            'camelcasename',
            header: $translator->translate('invoice.generator.relation.form.camelcase.name'),
            content: static fn (GentorRelation $model) => Html::encode($model->getCamelcase_name())
        ),
        new ActionColumn(
            content: static fn(GentorRelation $model): string => 
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.view')
            ])
            ->content('🔎')
            ->encode(false)
            ->href('generatorrelation/view/'. $model->getRelation_id())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(GentorRelation $model): string => 
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.edit')
            ])
            ->content('✎')
            ->encode(false)
            ->href('generatorrelation/edit/'. $model->getRelation_id())
            ->render(),
        ),
        new ActionColumn(
            content: static fn(GentorRelation $model): string => 
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('generatorrelation/delete/'. $model->getRelation_id())
            ->render(),
        ),
    ];       
?>
<?php
    $toolbarString = 
        Form::tag()->post($urlGenerator->generate('generatorrelation/index'))->csrf($csrf)->open() .    
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close();
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('invoice.generator.relations'), 
        ''
    );      
    echo GridView::widget()
    ->bodyRowAttributes(['class' => 'align-middle'])
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-generator'])
    ->columns(...$columns)
    ->dataReader($paginator)
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w73-grid')
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