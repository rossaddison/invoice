<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @see App\Widget\Bootstrap5ModalPdf $this->layoutParameters['iframeWithPdf']
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var string $iframeWithPdf
 * @var string $type
 */

?>
<?php echo Html::openTag('div', [
    'id'        => 'modal-layout-modal-pdf-'.$type,
    'class'     => 'modal',
    'tab-index' => '-1']); ?>
    <?php echo Html::openTag('div', ['class' => 'modal-dialog']); ?>
        <?php echo Html::openTag('div', ['class' => 'modal-content']); ?>
            <?php echo Html::openTag('div', ['class' => 'modal-header']); ?>
                <?php echo Html::openTag('h5', ['class' => 'modal-title']); ?>
                    <?php echo Html::openTag(
                        'button',
                        ['class'              => 'btn btn-light',
                            'type'            => 'button',
                            'data-bs-dismiss' => 'modal',
                        ],
                    ); ?>
                        <?php echo '❌'; ?>        
                    <?php echo Html::closeTag('button'); ?>
                <?php echo Html::closeTag('h5'); ?>
            <?php echo Html::closeTag('div'); ?>
            <?php echo Html::openTag('div', ['class' => 'modal-body']); ?>
                <?php echo $iframeWithPdf; ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>