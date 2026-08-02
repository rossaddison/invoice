<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Contract\ContractForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var int $client_id
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
    <?= Html::encode($title); ?>
<?= Html::closeTag('h1'); ?>
<?= $button::back(); ?>
<?= Html::openTag('div'); ?>
    <?php
        ReadOnlyField::render(
            $translator->translate('client'),
            $form->getClientId() !== null ? (string) $form->getClientId() : '',
        );
        ReadOnlyField::render($translator->translate('contract.reference'), $form->getReference());
        ReadOnlyField::render($translator->translate('contract.name'), $form->getName());
        ReadOnlyField::render(
            $translator->translate('contract.period.start'),
            $form->getPeriodStart() instanceof \DateTimeImmutable
                ? $form->getPeriodStart()->format('Y-m-d')
                : $form->getPeriodStart(),
        );
        ReadOnlyField::render(
            $translator->translate('contract.period.end'),
            $form->getPeriodEnd() instanceof \DateTimeImmutable
                ? $form->getPeriodEnd()->format('Y-m-d')
                : $form->getPeriodEnd(),
        );
    ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
