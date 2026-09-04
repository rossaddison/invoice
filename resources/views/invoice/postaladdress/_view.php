<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Infrastructure\Persistence\PostalAddress\PostalAddress $postalAddress
 * @var App\Invoice\PostalAddress\PostalAddressForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<string,list<string>> $errors
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
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= $pAdd = 'client.postaladdress.'; ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render(
                    $translator->translate($pAdd . 'street.name'),
                    $form->getStreetName(),
                );
ReadOnlyField::render(
    $translator->translate($pAdd . 'additional.street.name'),
    $form->getAdditionalStreetName(),
);
ReadOnlyField::render(
    $translator->translate($pAdd . 'building.number'),
    $form->getBuildingNumber(),
);
ReadOnlyField::render(
    $translator->translate($pAdd . 'city.name'),
    $form->getCityName(),
);
ReadOnlyField::render(
    $translator->translate($pAdd . 'postalzone'),
    $form->getPostalzone(),
);
ReadOnlyField::render(
    $translator->translate($pAdd . 'countrysubentity'),
    $form->getCountrysubentity(),
);
ReadOnlyField::render(
    $translator->translate($pAdd . 'country'),
    $form->getCountry(),
);
?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
