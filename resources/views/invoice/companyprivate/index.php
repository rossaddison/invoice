<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\OffsetPagination;

/**
 * @var \App\Invoice\Entity\CompanyPrivate $companyprivate
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var bool $canEdit
 * @var string $id
 */

echo $alert;
?>
<?= Html::openTag('h1'); ?>
    <?= $company_private; ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div'); ?>
<?php
    if ($canEdit) {
        echo Html::a('Add',
        $urlGenerator->generate('companyprivate/add'),
            ['class' => 'btn btn-outline-secondary btn-md-12 mb-3']
     );
}
?>
<?= Html::closeTag('div'); ?>

<?php
    $header = Div::tag()
    ->addClass('row')
    ->content(
        H5::tag()
            ->addClass('bg-primary text-white p-3 rounded-top')
            ->content(
                I::tag()->addClass('bi bi-receipt')
                        ->content(' ' . Html::encode($translator->translate('invoice.setting.company.private')))
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
    $columns = [
        new DataColumn(
            'company_public_name',
            header: $translator->translate('invoice.company.public'),
            content: static fn (object $model) => Html::encode($model->getCompany()?->getName())
        ),
        new DataColumn(
            'logo_filename',
            content: static fn (object $model) => Html::encode($model->getLogo_filename())
        ),
        new ActionColumn(
            content: static fn($model): string => Html::openTag('div', ['class' => 'btn-group']) .
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.view')
            ])
            ->content('🔎')
            ->encode(false)
            ->href('companyprivate/view/'. $model->getId())
            ->render() .
            Html::a()
            ->addAttributes([
                'class' => 'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.edit')
            ])
            ->content('✎')
            ->encode(false)
            ->href('companyprivate/edit/'. $model->getId())
            ->render() .
            Html::a()
            ->addAttributes([
                'class'=>'dropdown-button text-decoration-none', 
                'title' => $translator->translate('i.delete'),
                'type'=>'submit', 
                'onclick'=>"return confirm("."'".$translator->translate('i.delete_record_warning')."');"
            ])
            ->content('❌')
            ->encode(false)
            ->href('companyprivate/delete/'. $model->getId())
            ->render() . Html::closeTag('div')
        ),          
    ];
    echo GridView::widget()
    ->rowAttributes(['class' => 'align-middle'])
    ->columns(...$columns)
    ->dataReader($paginator)    
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->header($header)
    ->id('w53-grid')
    ->pagination(
    OffsetPagination::widget()
        ->paginator($paginator)
        ->render(),
    )
    ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
    ->summaryTemplate($grid_summary)
    ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
    ->emptyText((string)$translator->translate('invoice.invoice.no.records'))
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-companyprivate'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('companyprivate/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>