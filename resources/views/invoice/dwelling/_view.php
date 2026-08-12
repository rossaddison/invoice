<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Dwelling\DwellingForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string, list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $families
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
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= $button::back(); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render(
            $translator->translate('dwelling.family'),
            $selectLabel($families, $form->getFamilyId()),
        );
        ReadOnlyField::render($translator->translate('dwelling.house.number'), (string) $form->getHouseNumberNumeric());
        ReadOnlyField::render($translator->translate('dwelling.house.number.suffix'), $form->getHouseNumberSuffix() ?? '');
        ReadOnlyField::render($translator->translate('dwelling.flat.unit'), $form->getFlatUnit() ?? '');
        ReadOnlyField::render($translator->translate('dwelling.postcode'), $form->getPostcode() ?? '');
        ReadOnlyField::render($translator->translate('dwelling.latitude'), $form->getLatitude() !== null ? (string) $form->getLatitude() : '');
        ReadOnlyField::render($translator->translate('dwelling.longitude'), $form->getLongitude() !== null ? (string) $form->getLongitude() : '');
        ReadOnlyField::render($translator->translate('dwelling.source'), $form->getSource() ?? '');
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
