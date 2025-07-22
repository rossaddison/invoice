<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;

/*
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
<?php echo I::tag()
    ->addClass('bi bi-info-circle')
    ->addAttributes([
        'tooltip' => 'data-bs-toggle',
        'title'   => $s->isDebugMode(16),
    ])
    ->render(); ?>
<?php echo Html::openTag('div', ['class' => 'container py-5 h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'row d-flex justify-content-center align-items-center h-100']); ?>
<?php echo Html::openTag('div', ['class' => 'col-12 col-md-8 col-lg-6 col-xl-8']); ?>
<?php echo Html::openTag('div', ['class' => 'card border border-dark shadow-2-strong rounded-3']); ?>
<?php echo Html::openTag('div', ['class' => 'card-header']); ?>

<?php echo Html::openTag('h1', ['class' => 'fw-normal h3 text-center']); ?>
    <?php echo $translatedHeading; ?>
<?php echo Html::closeTag('h1'); ?>

<?php echo Html::openTag('div', ['id' => 'headerbar']); ?>
    <?php echo $translatedMessage; ?>
<?php echo Html::closeTag('div'); ?>

<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?> 

