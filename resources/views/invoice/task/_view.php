<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Task\TaskForm $form
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var \Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var array $statuses
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $projects
 * @psalm-var array<array-key, array<array-key, string>|string> $taxRates
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataStatus
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

$statuses = [
    1 => [
        'label' => $translator->translate('not.started'),
        'class' => 'secondary',
    ],
    2 => [
        'label' => $translator->translate('in.progress'),
        'class' => 'warning',
    ],
    3 => [
        'label' => $translator->translate('complete'),
        'class' => 'success',
    ],
    4 => [
        'label' => $translator->translate('invoiced'),
        'class' => 'primary',
    ],
];
$optionsDataStatus = [];
/**
 * @var int $key
 * @var array $status
 * @var string $status['label']
 */
foreach ($statuses as $key => $status) {
    if ($form->getStatus() !== 4 && $key === 4) {
        continue;
    }
    $optionsDataStatus[$key] = $status['label'];
}
?>
<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-body']); ?>
<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
<?= $translator->translate('tasks.form'); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render($translator->translate('name'), $form->getName());
ReadOnlyField::render($translator->translate('description'), $form->getDescription());
ReadOnlyField::render(
    $translator->translate('project'),
    $selectLabel($projects, $form->getProjectId()),
);
ReadOnlyField::render(
    $translator->translate('tax.rate'),
    $selectLabel($taxRates, $form->getTaxRateId()),
);
ReadOnlyField::render(
    $translator->translate('price'),
    $s->formatAmount($form->getPrice() ?? 0.00),
);
ReadOnlyField::render(
    $translator->translate('task.finish.date'),
    $form->getFinishDate() instanceof \DateTimeImmutable
        ? $form->getFinishDate()->format('Y-m-d')
        : (is_string($form->getFinishDate()) ? $form->getFinishDate() : ''),
);
ReadOnlyField::render(
    $translator->translate('status'),
    $selectLabel($optionsDataStatus, $form->getStatus()),
);
?>
<?= Html::closeTag('div'); ?>
<?= $button::back(); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
