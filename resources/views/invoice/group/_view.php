<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Group\GroupForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @var string $csrf
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translator->translate('group.form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render($translator->translate('name'), $form->getName());
        ReadOnlyField::render(
            $translator->translate('identifier.format'),
            $form->getIdentifierFormat(),
        );
        ReadOnlyField::render(
            $translator->translate('left.pad'),
            $form->getLeftPad() !== null ? (string) $form->getLeftPad() : '0',
        );
        ReadOnlyField::render(
            $translator->translate('next.id'),
            $form->getNextId() !== null ? (string) $form->getNextId() : '1',
        );
    ?>
    <?= $button::back(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
