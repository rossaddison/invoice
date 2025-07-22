<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see App\Widget\Bootstrap5ModalPdf $this->layoutParameters['iframeWithPdf']
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $iframeWithPdf
 * @var string $type
 */

?>
<?= Html::openTag('div', [
    'id' => 'modal-layout-modal-pdf-' . $type,
    'class' => 'modal',
    'tab-index' => '-1']); ?>
    <?= Html::openTag('div', ['class' => 'modal-dialog']); ?>
        <?= Html::openTag('div', ['class' => 'modal-content']); ?>
            <?= Html::openTag('div', ['class' => 'modal-header']); ?>
                <?= Html::openTag('h5', ['class' => 'modal-title']); ?>
                    <?= Html::openTag(
                        'button',
                        ['class' => 'btn btn-light',
                            'type' => 'button',
                            'data-bs-dismiss' => 'modal',
                        ],
                    ); ?>
                        <?= '❌'; ?>        
                    <?= Html::closeTag('button'); ?>
                <?= Html::closeTag('h5'); ?>
            <?= Html::closeTag('div'); ?>
            <?= Html::openTag('div', ['class' => 'modal-body']); ?>
                <?php echo $iframeWithPdf; ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>