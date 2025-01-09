<?php

declare(strict_types=1);

use App\Invoice\Entity\TaxRate;
use Yiisoft\Data\Paginator\OffsetPaginator;
use Yiisoft\Data\Paginator\PageToken;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Div;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Html\Tag\H5;
use Yiisoft\Html\Tag\I;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Yii\DataView\Column\ActionColumn;
use Yiisoft\Yii\DataView\Column\DataColumn;
use Yiisoft\Yii\DataView\GridView;
use Yiisoft\Yii\DataView\YiiRouter\UrlCreator;

/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\GridComponents $gridComponents
 * @var string $alert
 * @var string $csrf
 * @var CurrentRoute $currentRoute
 * @var Yiisoft\Data\Cycle\Reader\EntityReader $taxrates 
 * @var Yiisoft\Data\Paginator\OffsetPaginator $paginator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var WebView $this
 * @psalm-var positive-int $page
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
        ->href($urlGenerator->generate($currentRoute->getName() ?? 'taxrate/index'))
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
                content: static fn (TaxRate $model) => Html::encode($model->getTaxRateId())
            ),
            new DataColumn(
                'tax_rate_name',
                header: $translator->translate('i.tax_rate_name'),
                content: static fn (TaxRate $model) => Html::encode($model->getTaxRateName())
            ),
            new DataColumn(
                'tax_rate_percent',
                header: $translator->translate('i.tax_rate_percent'),
                content: static fn (TaxRate $model) => Html::encode($model->getTaxRatePercent())
            ),
            new DataColumn(
                'peppol_tax_rate_code',
                header: $translator->translate('invoice.peppol.tax.rate.code'),
                content: static fn (TaxRate $model) => Html::encode($model->getPeppolTaxRateCode())
            ),
            new DataColumn(
                'storecove_tax_type',
                header: $translator->translate('invoice.storecove.tax.rate.code'),
                content: static fn (TaxRate $model) => Html::encode(ucfirst(str_replace('_', ' ', $model->getStorecoveTaxType())))
            ),
            new DataColumn(
                'tax_rate_default',
                header: $translator->translate('invoice.default'),
                content: static fn (TaxRate $model) => Html::encode($model->getTaxRateDefault() == '1' ? 
                                                                  ($translator->translate('i.active').' '.'✔️' ) : 
                                                                   $translator->translate('i.inactive').' '.'❌')
            ),
            new ActionColumn(
                content: static function(TaxRate $model) use ($translator) : string { 
                $taxRateId = $model->getTaxRateId();
                if (null!==$taxRateId) {
                    return Html::openTag('div', ['class' => 'btn-group']) .
                        Html::a()
                        ->addAttributes([
                            'class' => 'dropdown-button text-decoration-none', 
                            'title' => $translator->translate('i.view')
                        ])
                        ->content('🔎')
                        ->encode(false)
                        ->href('taxrate/view/'. $taxRateId)
                        ->render() .
                        Html::a()
                        ->addAttributes([
                            'class' => 'dropdown-button text-decoration-none', 
                            'title' => $translator->translate('i.edit')
                        ])
                        ->content('✎')
                        ->encode(false)
                        ->href('taxrate/edit/'. $taxRateId)
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
                        ->href('taxrate/delete/'. $taxRateId)
                        ->render() . Html::closeTag('div');
                } else {
                    return '';
                }
            }),
        ];
    ?>
    <?php 
    
    $paginator = (new OffsetPaginator($taxrates))
        ->withPageSize($s->positiveListLimit())
        ->withCurrentPage($page)
        ->withToken(PageToken::next((string)$page)); 
    
    $grid_summary = $s->grid_summary(
        $paginator, 
        $translator, 
        (int)$s->getSetting('default_list_limit'), 
        $translator->translate('i.tax.rates'),
        ''
    );           
    $toolbarString = Form::tag()->post($urlGenerator->generate('taxrate/index'))->csrf($csrf)->open() .
            Div::tag()->addClass('float-end m-3')->content($toolbarReset)->encode(false)->render() .
            Form::tag()->close();
    echo GridView::widget()    
        ->bodyRowAttributes(['class' => 'align-middle'])
        ->tableAttributes(['class' => 'table table-striped text-center h-75','id'=>'table-taxrate'])
        ->columns(...$columns)
        ->dataReader($paginator)
        ->urlCreator(new UrlCreator($urlGenerator))    
        ->headerRowAttributes(['class'=>'card-header bg-info text-black'])
        ->header($header)
        ->id('w101-grid')
        ->paginationWidget($gridComponents->offsetPaginationWidget($paginator))
        ->summaryAttributes(['class' => 'mt-3 me-3 summary text-end'])
        ->summaryTemplate($grid_summary)
        ->emptyTextAttributes(['class' => 'card-header bg-warning text-black'])
        ->emptyText($translator->translate('invoice.invoice.no.records'))
        ->toolbar($toolbarString);
    ?>
