<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Widget\Bootstrap5ModalQuote $this->layoutParameters['form']
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $form
 * @var string $title
 * @var string $type
 */

$titleId = 'modal-add-' . $type . '-title';
?>
<?= Html::openTag('div', [
    'id' => 'modal-add-' . $type,
    'class' => 'modal',
    // WCAG 2.4.3: was the non-existent attribute 'tab-index' (typo for
    // 'tabindex') -- Bootstrap 5's modal.js calls this._element.focus()
    // on show, a no-op on a plain <div> with no valid tabindex, so focus
    // never actually moved into this modal when it opened. Same bug,
    // same fix as inv/modal_layout.php.
    'tabindex' => '-1',
    'aria-labelledby' => $titleId]); ?>
    <?= Html::openTag('div', ['class' => 'modal-dialog']); ?>
        <?= Html::openTag('div', ['class' => 'modal-content']); ?>
            <?= Html::openTag('div', ['class' => 'modal-header']); ?>
                <?= Html::openTag('h5', ['class' => 'modal-title', 'id' => $titleId]); ?>
                    <?= Html::encode($title); ?>
                <?= Html::closeTag('h5'); ?>
                <?= Html::openTag(
                    'button',
                    ['class' => 'btn btn-light',
                        'type' => 'button',
                        'data-bs-dismiss' => 'modal',
                        'aria-label' => $translator->translate('close'),
                    ],
                ); ?>
                    <?= '❌'; ?>
                <?= Html::closeTag('button'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'modal-body']); ?>
                <?php echo $form; ?>
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
