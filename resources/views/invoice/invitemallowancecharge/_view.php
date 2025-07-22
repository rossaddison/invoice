<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/*
 * @var App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $allowance_charges
 * @var string $alert
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataAllowanceCharge
 */
?>

<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>
<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?php echo Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('InvItemAllowanceChargeForm')
    ->open();
?>

<?php echo Html::openTag('div', ['class' => 'headerbar']); ?>
        <?php echo Html::openTag('h1'); ?>
            <?php echo Html::encode($title); ?>
        <?php echo Html::closeTag('h1'); ?>
<?php echo Html::closeTag('div'); ?>

<?php echo Html::openTag('div', ['id' => 'content']); ?>
    <?php echo Html::openTag('div', ['class' => 'row']); ?>   
        <?php echo Html::openTag('div', ['class' => 'input-group']); ?>
            <?php
                $optionsDataAllowanceCharge = [];
/**
 * @var App\Invoice\Entity\AllowanceCharge $allowance_charge
 */
foreach ($allowance_charges as $allowance_charge) {
    $optionsDataAllowanceCharge[$allowance_charge->getId()] = ($allowance_charge->getIdentifier()
    ? $translator->translate('allowance.or.charge.charge')
    : $translator->translate('allowance.or.charge.allowance'))
    .' '.$allowance_charge->getReason()
    .' '.$allowance_charge->getReasonCode()
    .' '.($allowance_charge->getTaxRate()?->getTaxRateName() ?? '')
    .' '.$translator->translate('allowance.or.charge.allowance');
}
?>
            <?php echo Field::select($form, 'allowance_charge_id')
    ->label($translator->translate('allowance.or.charge.item'))
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->optionsData($optionsDataAllowanceCharge)
    ->value($form->getAllowance_charge_id())
    ->prompt($translator->translate('none'));
?>
            <?php echo Field::text($form, 'amount')
    ->label($translator->translate('amount').'('.$s->getSetting('currency_symbol').')')
    ->addInputAttributes([
        'class'    => 'form-control',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value($s->format_amount($form->getAmount() ?? 0.00));
?>    
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>         
<?php echo $button::back(); ?>
<?php echo Form::tag()->close(); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
