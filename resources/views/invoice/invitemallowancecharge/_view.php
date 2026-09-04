<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$optionsDataAllowanceCharge = [];
/**
 * @var App\Infrastructure\Persistence\AllowanceCharge\AllowanceCharge $allowance_charge
 */
foreach ($allowance_charges as $allowance_charge) {
    $optionsDataAllowanceCharge[$allowance_charge->reqId()]
    = ($allowance_charge->getIdentifier()
    ? $translator->translate('allowance.or.charge.charge')
    : $translator->translate('allowance.or.charge.allowance'))
    . ' ' . ($allowance_charge->getReason())
    . ' ' . ($allowance_charge->getReasonCode())
    . ' ' . ($allowance_charge->getTaxRate()?->getTaxRateName() ?? '')
    . ' ' . ($translator->translate('allowance.or.charge.allowance'));
}
$selectLabel = static function (array $optionsData, int|string|null $value): string {
    if ($value === null || $value === '') {
        return '';
    }
    $key = (string) $value;
    /** @var string|array|null $label */
    $label = $optionsData[$key] ?? null;
    return is_string($label) ? $label : $key;
};
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render(
                    $translator->translate('allowance.or.charge.item.invoice'),
                    $selectLabel($optionsDataAllowanceCharge, $form->getAllowanceChargeId()),
                );
ReadOnlyField::render(
    $translator->translate('text-end.inv.item') . '(' . $s->getSetting('currency_symbol') . ')',
    $s->formatAmount($form->getAmount() ?? 0.00),
);
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
