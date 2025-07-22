<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see id="add-inv-allowance-charge" triggered by <a href="#add-inv-allowance-charge" data-bs-toggle="modal"  style="text-decoration:none"> on views/inv/view.php
 * Related logic: see InvController/save_inv_allowance_charge
 * Related logic: see echo $modal_add_allowance_charge; at BOTTOM resources/views/invoice/inv/view.php
 * @var string $modal_add_allowance_charge_form
 * @var string $type
 */

?>
<?php echo Html::openTag('div', [
    'id'        => 'add-inv-allowance-charge',
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
                <?php echo $modal_add_allowance_charge_form; ?>    
            <?php echo Html::closeTag('div'); ?>
        <?php echo Html::closeTag('div'); ?>
    <?php echo Html::closeTag('div'); ?>
<?php echo Html::closeTag('div'); ?>
   