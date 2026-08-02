<?php

declare(strict_types=1);

use App\Widget\ReadOnlyField;
use Yiisoft\Html\Html;

/**
 * @var App\Invoice\ProductImage\ProductImageForm $form
 * @var App\Widget\Button $button
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $actionName
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

// A pure display page — see docs/READONLY_VIEW_FIELDS_AUGUST_2026.md.
?>

<?= Html::openTag('div', ['class' => 'container-fluid py-3']); ?>
<?= Html::openTag('div', ['class' => 'row justify-content-center']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-lg-10 col-xl-10']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= Html::encode($title) ?>
<?= Html::closeTag('h1'); ?>
<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $button::back(); ?>
    <?= Html::openTag('div', ['id' => 'content']); ?>
        <?= Html::openTag('div', ['class' => 'row']); ?>
            <?= Html::openTag('div'); ?>
                <?php
                    ReadOnlyField::render(
                        $translator->translate('date'),
                        $form->getUploadedDate() instanceof \DateTimeImmutable
                            ? ($form->getUploadedDate())->format('Y-m-d')
                            : $form->getUploadedDate(),
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
                ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
