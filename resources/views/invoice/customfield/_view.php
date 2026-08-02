<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\CustomField\CustomFieldForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $custom_tables
 * @var string $actionName
 * @var string $csrf
 * @var string $title
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
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= $button::back(); ?>
<?= Html::openTag('div'); ?>
    <?php
        $table = $form->table ?? '';
        ReadOnlyField::render(
            $translator->translate('table'),
            strlen($table) > 0 && isset($custom_tables[$table])
                ? ucfirst($s->lang((string) $custom_tables[$table]))
                : '',
        );
        ReadOnlyField::render($translator->translate('label'), $form->label);
        ReadOnlyField::render(
            $translator->translate('type'),
            $translator->translate(str_replace('-', '_', strtolower($form->type ?? ''))),
        );
        ReadOnlyField::render(
            $translator->translate('custom.field.location'),
            $form->location !== null ? (string) $form->location : '',
        );
        ReadOnlyField::render(
            $translator->translate('order'),
            $form->order !== null ? (string) $form->order : '',
        );
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
