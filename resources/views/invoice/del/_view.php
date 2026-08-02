<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * Related logic: see App\Invoice\DeliveryLocation\DeliveryLocationController function view
 * @var App\Invoice\DeliveryLocation\DeliveryLocationForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $actionQueryParameters
 * @var array $electronic_address_scheme
 * @var string $actionName
 * @var string $csrf
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataEAS
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

$optionsDataEAS = [];
/**
 * Related logic: see src/Invoice/Helpers/Peppol/PeppolArrays.php function electronicAddressScheme
 * Related logic: see https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/
 * @var array $value
 */
foreach ($electronic_address_scheme as $value) {
    $optionsDataEAS[(string) $value['Id']] = (string) $value['Id'] . str_repeat("-", 10) . (string) $value['Name'];
}
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render(
                    $translator->translate('common.date.created'),
                    $form->getDateCreated()
                        ->setTimeZone(new DateTimeZone($s->getSetting('time_zone') ?: 'Europe/London'))
                        ->format('Y-m-d H:i:s'),
                );
                ReadOnlyField::render(
                    $translator->translate('common.date.modified'),
                    $form->getDateModified()
                        ->setTimeZone(new DateTimeZone($s->getSetting('time_zone') ?: 'Europe/London'))
                        ->format('Y-m-d H:i:s'),
                );
                ReadOnlyField::render($translator->translate('name'), $form->getName());
                ReadOnlyField::render(
                    $translator->translate('delivery.location.building.number'),
                    $form->getBuildingNumber(),
                );
                ReadOnlyField::render($translator->translate('street.address'), $form->getAddress1());
                ReadOnlyField::render($translator->translate('street.address.2'), $form->getAddress2());
                ReadOnlyField::render($translator->translate('city'), $form->getCity());
                ReadOnlyField::render($translator->translate('state'), $form->getState());
                ReadOnlyField::render($translator->translate('zip'), $form->getZip());
                ReadOnlyField::render($translator->translate('country'), $form->getCountry());
            ?>
            <?= Html::openTag('div'); ?>
                <?= Html::a(
                    $translator->translate('delivery.location.global.location.number'),
                    'https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-Delivery/cac-DeliveryLocation/cbc-ID/',
                    ['class' => 'text-decoration-none'],
                ); ?>
                <?php
                    ReadOnlyField::render(
                        $translator->translate('delivery.location.global.location.number'),
                        $form->getGlobalLocationNumber(),
                    );
                ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div'); ?>
                <?= Html::a('EAS', 'https://docs.peppol.eu/poacc/upgrade-3/codelist/eas'); ?>
                <?php
                    ReadOnlyField::render(
                        $translator->translate('delivery.location.electronic.address.scheme'),
                        $selectLabel($optionsDataEAS, $form->getElectronicAddressScheme()),
                    );
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
