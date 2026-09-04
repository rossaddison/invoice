<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Infrastructure\Persistence\Inv\Inv $inv
 * @var App\Invoice\Delivery\DeliveryForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $dels
 * @var int $del_count
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
$optionsDataDel = [];
/**
 * @var App\Infrastructure\Persistence\DeliveryLocation\DeliveryLocation $del
 */
foreach ($dels as $del) {
    $optionsDataDel[$del->reqId()] = ($del->getAddress1() ?? '')
            . ', '
            . ($del->getAddress2() ?? '')
            . ', '
            . ($del->getCity() ?? '')
            . ', '
            . ($del->getZip() ?? '');
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
$formatDate = static function (string|DateTimeImmutable $value): string {
    return $value instanceof DateTimeImmutable ? $value->format('Y-m-d') : '';
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
                    $translator->translate('delivery.date.created') . ' (' . $dateHelper->display() . ')',
                    $formatDate($form->getDateCreated()),
                );
ReadOnlyField::render(
    $translator->translate('delivery.date.modified') . ' (' . $dateHelper->display() . ')',
    $formatDate($form->getDateModified()),
);
ReadOnlyField::render(
    $translator->translate('delivery.start.date') . ' (' . $dateHelper->display() . ')',
    $formatDate($form->getStartDate()),
);
ReadOnlyField::render(
    $translator->translate('delivery.actual.delivery.date') . ' (' . $dateHelper->display() . ')',
    $formatDate($form->getActualDeliveryDate()),
);
ReadOnlyField::render(
    $translator->translate('delivery.end.date') . ' (' . $dateHelper->display() . ')',
    $formatDate($form->getEndDate()),
);
ReadOnlyField::render(
    $translator->translate('delivery.location'),
    $del_count > 0 ? $selectLabel($optionsDataDel, $form->getDeliveryLocationId()) : '',
);
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
