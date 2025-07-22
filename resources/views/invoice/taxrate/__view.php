<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\TaxRate\TaxRateForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $title
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataPeppolTaxRateCode
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStoreCoveTaxType
 */
?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('TaxRateForm')
    ->open(); ?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>    
    <?php echo Html::encode($title); ?>
<?php echo Html::closeTag('h1'); ?>
<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $button::back(); ?>
    <?php echo Html::openTag('div', ['id' => 'content']); ?>
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
            <?php echo Html::openTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'tax_rate_name')
        ->label($translator->translate('tax.rate.name'))
        ->value(Html::encode($form->getTaxRateName() ?? ''))
        ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'tax_rate_percent')
    ->label($translator->translate('tax.rate.percent'))
    ->value(Html::encode($form->getTaxRatePercent() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::checkbox($form, 'tax_rate_default')
    ->inputLabelAttributes(['class' => 'form-check-label'])
    ->inputClass('form-check-input')
    ->ariaDescribedBy($translator->translate('tax.rate.default'))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::text($form, 'tax_rate_code')
    ->label($translator->translate('tax.rate.code'))
    ->value(Html::encode($form->getTaxRateCode() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::select($form, 'peppol_tax_rate_code')
    ->label($translator->translate('peppol.tax.rate.code'))
    ->optionsData($optionsDataPeppolTaxRateCode)
    ->value(Html::encode($form->getPeppolTaxRateCode() ?? ''))->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
                <?php echo Html::openTag('div', ['class' => 'mb-3 form-group']); ?>
                    <?php echo Field::select($form, 'storecove_tax_type')
    ->label($translator->translate('storecove.tax.rate.code'))
    ->optionsData($optionsDataStoreCoveTaxType)
    ->value(Html::encode($form->getStorecoveTaxType() ?? ''))
    ->disabled(true);
?>
                <?php echo Html::closeTag('div'); ?>
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>