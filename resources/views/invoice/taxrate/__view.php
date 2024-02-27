<?php

declare(strict_types=1); 

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $action
 * @var string $title
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate(...$action))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('TaxRateForm')
    ->open() ?>

<?= Html::openTag('div',['class'=>'container py-5 h-100']); ?>
<?= Html::openTag('div',['class'=>'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div',['class'=>'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',['class'=>'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div',['class'=>'card-header']); ?>

<?= Html::openTag('h1',['class'=>'fw-normal h3 text-center']); ?>    
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back($translator); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_name')
                        ->label($translator->translate('i.tax_rate_name'), ['class' => 'form-label'])
                        ->value(Html::encode($form->getTax_rate_name() ?? ''))
                        ->disabled(true); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_percent')
                        ->label($translator->translate('invoice.tax.rate.percent'), ['class' => 'form-label'])
                        ->value(Html::encode($form->getTax_rate_percent() ?? ''))
                        ->disabled(true); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::checkbox($form, 'tax_rate_default')
                        ->inputLabelAttributes(['class' => 'form-check-label'])    
                        ->enclosedByLabel(true)
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($translator->translate('i.tax_rate_default'))
                        ->disabled(true);
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_code')
                        ->label($translator->translate('invoice.invoice.tax.rate.code'), ['class' => 'form-label'])
                        ->value(Html::encode($form->getTax_rate_code() ?? ''))
                        ->disabled(true); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'peppol_tax_rate_code')
                        ->label($translator->translate('invoice.peppol.tax.rate.code'), ['class' => 'form-label'])
                        ->optionsData($optionsDataPeppolTaxRateCode)
                        ->value(Html::encode($form->getPeppol_tax_rate_code() ?? ''))->disabled(true); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'storecove_tax_type')
                        ->label($translator->translate('invoice.storecove.tax.rate.code'), ['class' => 'form-label'])
                        ->optionsData($optionsDataStoreCoveTaxType)
                        ->value(Html::encode($form->getStorecove_tax_type() ?? ''))
                        ->disabled(true); 
                    ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close() ?>