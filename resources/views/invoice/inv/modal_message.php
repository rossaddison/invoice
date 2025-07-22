<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;

/**
 * Related logic: see .\resources\views\invoice\inv\modal_message_layout.php
 * Related logic: see .\src\widget\Bootstrap5ModalTranslatorMessageWithoutAction.php
 * Related logic: see example .\src\Invoice\Inv\InvController.php action view
 * Related logic: see example .\resources\views\invoice\inv\view.php
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $translatedHeading
 * @var string $translatedMessage
 *
 */
?>
<?= I::tag()
    ->addClass('bi bi-info-circle')
    ->addAttributes([
        'tooltip' => 'data-bs-toggle',
        'title' => $s->isDebugMode(16),
    ])
    ->render(); ?>
<?= Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?= Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?= Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?= Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?= Html::openTag('div', ['class' => 'card-header']); ?>

<?= Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?= $translatedHeading; ?>
<?= Html::closeTag('h1'); ?>

<?= Html::openTag('div', ['id' => 'headerbar']); ?>
    <?= $translatedMessage; ?>
<?= Html::closeTag('div'); ?>

<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?> 

