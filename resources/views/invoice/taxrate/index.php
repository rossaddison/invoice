<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
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
 * @var \App\Invoice\Entity\TaxRate $taxRate
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
                            ->content(' ' . Html::encode($translator->translate('invoice.invoice.tax.rate')))
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
<?= Html::openTag('div'); ?>
    <?= Html::openTag('h5'); ?>
        <?= $translator->translate('invoice.invoice.tax.rate'); ?>
    <?= Html::closeTag('h5'); ?>    
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div'); ?>
    <?= Html::openTag('div', ['class' => 'btn-group']); ?>
        <?= A::tag()
            ->addClass('btn btn-success')
            ->content(I::tag()
                      ->addClass('fa fa-plus')) 
            ->href($urlGenerator->generate('taxrate/add')); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>


<br>
    <?php
        $columns = [
            new DataColumn(
                'tax_rate_id',
                header: $translator->translate('i.id'),
                content: static fn (object $model) => Html::encode($model->getTax_rate_id())
            ),
            new DataColumn(
                'tax_rate_name',
                header: $translator->translate('i.tax_rate_name'),
                content: static fn (object $model) => Html::encode($model->getTax_rate_name())
            ),
            new DataColumn(
                'tax_rate_percent',
                header: $translator->translate('i.tax_rate_percent'),
                content: static fn (object $model) => Html::encode($model->getTax_rate_percent())
            ),
            new DataColumn(
                'peppol_tax_rate_code',
                header: $translator->translate('invoice.peppol.tax.rate.code'),
                content: static fn (object $model) => Html::encode($model->getPeppol_tax_rate_code())
            ),
            new DataColumn(
                'storecove_tax_type',
                header: $translator->translate('invoice.storecove.tax.rate.code'),
                content: static fn (object $model) => Html::encode(ucfirst(str_replace('_', ' ', $model->getStorecove_tax_type())))
            ),
            new DataColumn(
                'tax_rate_default',
                header: $translator->translate('invoice.default'),
                content: static fn (object $model) => Html::encode($model->getTax_rate_default() == '1' ? 
                                                                  ($translator->translate('i.active').' '.'✔️' ) : 
                                                                   $translator->translate('i.inactive').' '.'❌')
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
                ->href('/invoice/taxrate/view/'. $model->getTax_rate_id())
                ->render() .
                Html::a()
                ->addAttributes([
                    'class' => 'dropdown-button text-decoration-none', 
                    'title' => $translator->translate('i.edit')
                ])
                ->content('✎')
                ->encode(false)
                ->href('/invoice/taxrate/edit/'. $model->getTax_rate_id())
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
                ->href('/invoice/taxrate/delete/'. $model->getTax_rate_id())
                ->render() . Html::closeTag('div')
            ),
        ];
    ?>
    <?= GridView::widget()    
        ->columns(...$columns)
        ->dataReader($paginator)    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ////->filterPosition('header')
        //->filterModelName('company')
        ->header($header)
        ->id('w101-grid')
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
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-taxrate'])
        ->toolbar(
            Form::tag()->post($urlGenerator->generate('taxrate/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close()
        );
    ?>
