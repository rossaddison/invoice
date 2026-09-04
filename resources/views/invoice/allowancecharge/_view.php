<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\AllowanceCharge\AllowanceChargeForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$ac = 'allowance.or.charge.';
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
            $translator->translate('allowance.or.charge'),
            $translator->translate($form->getIdentifier() === true ? $ac . 'charge' : $ac . 'allowance'),
        );
ReadOnlyField::render(
    $translator->translate($ac . 'level'),
    $translator->translate($form->getLevel() === 1 ? 'yes' : 'no'),
);
ReadOnlyField::render($translator->translate($ac . 'reason.code'), $form->getReasonCode());
ReadOnlyField::render($translator->translate($ac . 'reason'), $form->getReason());
ReadOnlyField::render(
    $translator->translate($ac . 'multiplier.factor.numeric'),
    $form->getMultiplierFactorNumeric() !== null ? (string) $form->getMultiplierFactorNumeric() : '',
);
ReadOnlyField::render(
    $translator->translate($ac . 'amount'),
    $form->getAmount() !== null ? (string) $form->getAmount() : '',
);
ReadOnlyField::render(
    $translator->translate($ac . 'base.amount'),
    $form->getBaseAmount() !== null ? (string) $form->getBaseAmount() : '',
);
?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
