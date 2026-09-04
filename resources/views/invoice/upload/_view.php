<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Upload\UploadForm $form
 * @var App\Widget\Button $button
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $actionName
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @var string $csrf
 * @var string $title
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataClients
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
                ReadOnlyField::render(
                    $translator->translate('clients'),
                    $selectLabel($optionsDataClients, $form->getClientId()),
                );
ReadOnlyField::render(
    $translator->translate('upload.url.key'),
    $form->getUrlKey(),
);
ReadOnlyField::render(
    $translator->translate('upload.filename.original'),
    $form->getFileNameOriginal(),
);
ReadOnlyField::render(
    $translator->translate('upload.filename.new'),
    $form->getFileNameNew(),
);
ReadOnlyField::render(
    $translator->translate('upload.description'),
    $form->getDescription(),
);
ReadOnlyField::render(
    $translator->translate('date'),
    $form->getUploadedDate() instanceof \DateTimeImmutable
        ? ($form->getUploadedDate())->format('Y-m-d')
        : $form->getUploadedDate(),
);
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
