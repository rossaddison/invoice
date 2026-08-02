<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\InvRecurring\InvRecurringForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Widget\Button $button
 * @var DateTimeImmutable $invDateCreated
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$optionsDataFrequency = [];
/**
 * @var string $key
 * @var string $value
 */
foreach ($numberHelper->recurFrequencies() as $key => $value) {
    $optionsDataFrequency[$key] = $translator->translate($value);
}
$frequencyLabel = $form->getFrequency() !== null && $form->getFrequency() !== ''
    ? ($optionsDataFrequency[$form->getFrequency()] ?? $form->getFrequency())
    : '';

$formatMaybeDate = static function (string|DateTimeImmutable|null $value): string {
    return $value instanceof DateTimeImmutable ? $value->format('Y-m-d') : (string) $value;
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
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::openTag('p'); ?>
                    <?= $translator->translate('recurring.original.invoice.date') . '(' . $dateHelper->display() . ')'; ?>
                    <?= $invDateCreated->format('Y-m-d'); ?>
                <?= Html::closeTag('p'); ?>
                <?php
                    ReadOnlyField::render($translator->translate('recurring.frequency'), $frequencyLabel);
                    ReadOnlyField::render(
                        $translator->translate('start') . ' (' . $dateHelper->display() . ') ',
                        $formatMaybeDate($form->getStart()),
                    );
                    ReadOnlyField::render(
                        $translator->translate('next') . ' (' . $dateHelper->display() . ') ',
                        $formatMaybeDate($form->getNext()),
                    );
                    ReadOnlyField::render(
                        $translator->translate('end') . ' (' . $dateHelper->display() . ') ',
                        $formatMaybeDate($form->getEnd()),
                    );
                ?>
                <?= $button::back(); ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
