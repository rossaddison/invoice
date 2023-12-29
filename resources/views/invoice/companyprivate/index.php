<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
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
        new DataColumn(
            header: $translator->translate('i.view'),    
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(
                       Html::tag('i','',['class'=>'fa fa-eye fa-margin']),
                       $urlGenerator->generate('companyprivate/view',['id'=>$model->getId()]),[])
                       ->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.edit'),    
            content: static function ($model) use ($urlGenerator): string {
               return Html::a(
                       Html::tag('i','',['class'=>'fa fa-edit fa-margin']),
                       $urlGenerator->generate('companyprivate/edit',['id'=>$model->getId()]),[])
                       ->render();
            }
        ),
        new DataColumn(
            header: $translator->translate('i.delete'),    
            content: static function ($model) use ($translator, $urlGenerator): string {
               return Html::a( Html::tag('button',
                    Html::tag('i','',['class'=>'fa fa-trash fa-margin']),
                    [
                        'type'=>'submit', 
                        'class'=>'dropdown-button',
                        'onclick'=>"return confirm("."'".$translator->translate('invoice.company.private.logo.will.be.removed.from.uploads.and.public.folder')."');"
                    ]
                    ),
                    $urlGenerator->generate('companyprivate/delete',['id'=>$model->getId()]),[]                                         
                )->render();
            }
        ),          
    ];
    echo GridView::widget()    
    ->columns(...$columns)
    ->dataReader($paginator)    
    ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
    ->filterPosition('header')
    ->filterModelName('companyprivate')
    ->header($header)
    ->id('w53-grid')
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
    ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-companyprivate'])
    ->toolbar(
        Form::tag()->post($urlGenerator->generate('companyprivate/index'))->csrf($csrf)->open() .
        Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
        Form::tag()->close()
    );
?>