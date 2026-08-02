<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\CompanyPrivate\CompanyPrivateForm $form
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Widget\Button $button
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $company_public
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 * @psalm-var array<array-key, array<array-key, string>|string> $optionsDataCompany
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
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?php
                ReadOnlyField::render('Company Name', $form->getCompanyPublicName());
                ReadOnlyField::render($translator->translate('tax.code'), $form->getTaxCode());
                ReadOnlyField::render($translator->translate('user.iban'), $form->getIban());
                ReadOnlyField::render($translator->translate('bacs.sort.code'), $form->getBacsSortCode());
                ReadOnlyField::render(
                    $translator->translate('bacs.account.number'),
                    $form->getBacsAccountNumber(),
                );
                ReadOnlyField::render($translator->translate('gln'), $form->getGln());
                ReadOnlyField::render('RCC', $form->getRcc());
            ?>
            <?= Html::openTag('div', ['class' => 'container-fluid px-1']); ?>
                <?= Html::openTag('div', ['class' => 'p-3 border bg-light']); ?>
                    <?php
                        ReadOnlyField::render('Logo Filename', $form->getLogoFilename());
                    ?>
                    <?= Html::openTag('div', ['class' => 'mb-3']); ?>
                        <?= Html::img('/logo/' . ($form->getLogoFilename() ?? '#'))
                            ->height((int) ($form->getLogoHeight() ?? 0))
                            ->width((int) ($form->getLogoWidth() ?? 0)); ?>
                    <?= Html::closeTag('div'); ?>
                <?= Html::closeTag('div'); ?>
            <?= Html::closeTag('div'); ?>
            <?php
                ReadOnlyField::render(
                    $translator->translate('company.private.logo.width'),
                    $form->getLogoWidth(),
                );
                ReadOnlyField::render(
                    $translator->translate('company.private.logo.height'),
                    $form->getLogoHeight(),
                );
                ReadOnlyField::render(
                    $translator->translate('company.private.logo.margin'),
                    $form->getLogoMargin(),
                );
                ReadOnlyField::render(
                    $translator->translate('start.date') . ' (' . $dateHelper->display() . ')',
                    $form->getStartDate() instanceof \DateTimeImmutable
                        ? $form->getStartDate()->format('Y-m-d')
                        : (new \DateTimeImmutable('now'))->format('Y-m-d'),
                );
                ReadOnlyField::render(
                    $translator->translate('end.date') . ' (' . $dateHelper->display() . ')',
                    $form->getEndDate() instanceof \DateTimeImmutable
                        ? $form->getEndDate()->format('Y-m-d')
                        : (new \DateTimeImmutable('now'))->format('Y-m-d'),
                );
            ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
