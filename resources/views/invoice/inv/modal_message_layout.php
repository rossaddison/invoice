<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * This is a layout which will hold the form passed to it
 * Related logic: see App\Widget\Bootstrap5ModalTranslatorMessageWithoutAction
 * @var string $form
 * @var string $type
 */

?>
    
<?php echo Html::openTag('div', [
    'id'        => 'modal-message-'.$type,
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
                <?php echo $form; ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>    
