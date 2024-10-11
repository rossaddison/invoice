<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\TaxRate\TaxRateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator 
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName 
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataPeppolTaxRateCode
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStoreCoveTaxType
 */
?>

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
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
    <?= $button::back_save(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                <?= Field::errorSummary($form)
                    ->errors($errors)
                    ->header($translator->translate('invoice.error.summary'))
                    ->onlyCommonErrors()
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_name')
                        ->label($translator->translate('i.tax_rate_name'))
                        ->value(Html::encode($form->getTax_rate_name() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_percent')
                        ->label($translator->translate('invoice.tax.rate.percent'))
                        ->value(Html::encode($form->getTax_rate_percent() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::checkbox($form, 'tax_rate_default')
                        ->inputLabelAttributes(['class' => 'form-check-label'])   
                        ->inputClass('form-check-input')
                        ->ariaDescribedBy($translator->translate('i.tax_rate_default'));
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::text($form, 'tax_rate_code')
                        ->label($translator->translate('invoice.invoice.tax.rate.code'))
                        ->value(Html::encode($form->getTax_rate_code() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'peppol_tax_rate_code')
                        ->label($translator->translate('invoice.peppol.tax.rate.code'))
                        ->optionsData($optionsDataPeppolTaxRateCode)
                        ->value(Html::encode($form->getPeppol_tax_rate_code() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
                    ?>
                <?= Html::closeTag('div'); ?>
                <?= Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?= Field::select($form, 'storecove_tax_type')
                        ->label($translator->translate('invoice.storecove.tax.rate.code'))
                        ->optionsData($optionsDataStoreCoveTaxType)
                        ->value(Html::encode($form->getStorecove_tax_type() ?? ''))
                        ->hint($translator->translate('invoice.hint.this.field.is.not.required')); 
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