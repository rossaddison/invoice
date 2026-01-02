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

<?= Form::tag()
    ->post($urlGenerator->generate($actionName, $actionArguments))
    ->enctypeMultipartFormData()
    ->csrf($csrf)
    ->id('QuoteItemAllowanceChargeForm')
    ->open();
?> 

<?= $button::backSave(); ?>

<?= Html::openTag('div', ['class' => 'headerbar']); ?>
        <?= Html::openTag('h4');?>
            <?= Html::encode($title); ?>
        <?=Html::closeTag('h1'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::openTag('div', ['id' => 'content']); ?>
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
    ->addInputAttributes(['class' => 'form-control'])
    ->optionsData($optionsDataAllowanceCharge)
    ->value($form->getAllowance_charge_id())
    ->prompt($translator->translate('none'))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
        <?= Field::text($form, 'amount')
    ->label($translator->translate('amount.quote.item')
        . '(' . $s->getSetting('currency_symbol') . ')')
    ->addInputAttributes(['class' => 'form-control'])
    ->value($s->format_amount($form->getAmount() ?? 0.00))
    ->hint($translator->translate('hint.this.field.is.required'));
?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Form::tag()->close(); ?>
<?= Html::closeTag('div'); ?>
