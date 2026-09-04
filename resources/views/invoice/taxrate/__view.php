<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
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

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
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
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render($translator->translate('tax.rate.name'), $form->getTaxRateName());
ReadOnlyField::render(
    $translator->translate('tax.rate.percent'),
    $form->getTaxRatePercent() !== null ? (string) $form->getTaxRatePercent() : '',
);
ReadOnlyField::render(
    $translator->translate('tax.rate.default'),
    $translator->translate($form->getTaxRateDefault() === true ? 'yes' : 'no'),
);
ReadOnlyField::render($translator->translate('tax.rate.code'), $form->getTaxRateCode());
ReadOnlyField::render(
    $translator->translate('peppol.tax.rate.code'),
    $selectLabel($optionsDataPeppolTaxRateCode, $form->getPeppolTaxRateCode()),
);
ReadOnlyField::render(
    $translator->translate('storecove.tax.rate.code'),
    $selectLabel($optionsDataStoreCoveTaxType, $form->getStorecoveTaxType()),
);
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
