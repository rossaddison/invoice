<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\AllowanceCharge\AllowanceChargeForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $allowances
 * @var array $taxRates
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataReason
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataTax
 */

?>
<?php echo Html::openTag('h1'); ?>
    <?php echo Html::a($title, 'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-AllowanceCharge/', ['class' => 'btn btn-primary']); ?>
<?php echo Html::closeTag('h1'); ?>

<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('AllowanceChargeForm')
    ->open(); ?>
    <?php echo $button::backSave(); ?>
    <?php echo Html::openTag('div', ['id' => 'headerbar']); ?>    
        <?php echo Html::openTag('h1', ['class' => 'headerbar-title']); ?>
            <?php echo $title; ?>
        <?php echo Html::closeTag('h1'); ?>
    <?php echo Html::closeTag('div'); ?>
    <?php echo Html::openTag('div'); ?> 
        <?php echo Field::errorSummary($form)
            ->errors($errors)
            ->header($translator->translate('error.summary'))
            ->onlyProperties(...[
                'id',
                'reason',
                'multiplier_factor_numeric',
                'amount',
                'base_amount',
                'tax_rate_id'])
            ->onlyCommonErrors();
?>    
        <?php echo Html::openTag('div', ['class' => 'row']); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php echo Field::hidden($form, 'id')
            ->addInputAttributes([
                'class' => 'form-control',
            ])
            ->hideLabel()
            ->value(Html::encode($form->getId()));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php
    $optionsDataReason = [];
/**
 * @var string $value
 */
foreach ($allowances as $key => $value) {
    $optionsDataReason[$value] = $value;
}
?>
            <?php echo Field::select($form, 'reason')
    ->label($translator->translate('allowance.or.charge.reason'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id'    => 'reason',
    ])
    ->value(Html::encode($form->getReason() ?? 'Discount'))
    ->optionsData($optionsDataReason, true)
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php echo Field::text($form, 'multiplier_factor_numeric')
    ->label($translator->translate('allowance.or.charge.multiplier.factor.numeric'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('allowance.or.charge.multiplier.factor.numeric'),
        'class'       => 'form-control',
        'id'          => 'multiplier_factor_numeric',
    ])
    ->value(Html::encode($form->getMultiplierFactorNumeric() ?? '20'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>   
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php echo Field::text($form, 'amount')
            ->label($translator->translate('allowance.or.charge.amount'))
            ->addInputAttributes([
                'placeholder' => $translator->translate('allowance.or.charge.amount'),
                'class'       => 'form-control',
                'id'          => 'amount',
            ])
            ->value(Html::encode($form->getAmount() ?? ''))
            ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php echo Field::text($form, 'base_amount')
    ->label($translator->translate('allowance.or.charge.base.amount'))
    ->addInputAttributes([
        'placeholder' => $translator->translate('allowance.or.charge.base.amount'),
        'class'       => 'form-control',
        'id'          => 'base_amount',
    ])
    ->value(Html::encode($form->getBaseAmount() ?? '1000'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?php echo Html::closeTag('div'); ?>
        <?php echo Html::openTag('div', ['class' => 'mb3 form-group']); ?>
            <?php
    $optionsDataTax = [];
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
foreach ($taxRates as $taxRate) {
    $taxRateId = $taxRate->getTaxRateId();
    if (null !== $taxRateId) {
        $optionsDataTax[$taxRateId] = (string) $taxRateId
            .':  '
            .(string) $taxRate->getTaxRateName()
            .' '
            .(string) $taxRate->getTaxRatePercent();
    }
}
?>
            <?php echo Field::select($form, 'tax_rate_id')
    ->label($translator->translate('tax.rate'))
    ->addInputAttributes([
        'class' => 'form-control',
        'id'    => 'tax_rate_id',
    ])
    ->value($form->getTaxRateId() ?? '')
    ->optionsData($optionsDataTax, true)
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Form::tag()->close(); ?>