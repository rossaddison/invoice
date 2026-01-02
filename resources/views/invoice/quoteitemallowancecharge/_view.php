<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeForm $form
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

<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div',
    ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div',
    ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteItemAllowanceChargeForm')
    ->open();
?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h1');?>
            <?= Html::encode($title); ?>
        <?=Html::closeTag('h1'); ?>
<?= Html::closeTag('div'); ?>

<?= Html::openTag('div', ['id' => 'content']); ?>
    <?= Html::openTag('div', ['class' => 'row']); ?>   
        <?= Html::openTag('div', ['class' => 'input-group']); ?>
            <?php
                $optionsDataAllowanceCharge = [];
/**
 * @var App\Invoice\Entity\AllowanceCharge $allowance_charge
 */
foreach ($allowance_charges as $allowance_charge) {
    $optionsDataAllowanceCharge[$allowance_charge->getId()]
    = ($allowance_charge->getIdentifier()
    ? $translator->translate('allowance.or.charge.charge')
    : $translator->translate('allowance.or.charge.allowance'))
    . ' ' . ($allowance_charge->getReason())
    . ' ' . ($allowance_charge->getReasonCode())
    . ' ' . ($allowance_charge->getTaxRate()?->getTaxRateName() ?? '')
    . ' ' . ($translator->translate('allowance.or.charge.allowance'));
}
?>
            <?= Field::select($form, 'allowance_charge_id')
    ->label($translator->translate('allowance.or.charge.item.quote'))
    ->addInputAttributes([
        'class' => 'form-control',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->optionsData($optionsDataAllowanceCharge)
    ->value($form->getAllowance_charge_id())
    ->prompt($translator->translate('none'));
?>
            <?= Field::text($form, 'amount')
    ->label($translator->translate('amount')
        . '('
        . $s->getSetting('currency_symbol') . ')')
    ->addInputAttributes([
        'class' => 'form-control',
        'readonly' => 'readonly',
        'disabled' => 'disabled',
    ])
    ->value($s->format_amount($form->getAmount() ?? 0.00));
?>    
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>         
<?= $button::back(); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
