<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see id="add-inv-allowance-charge" triggered by <a href="#add-inv-allowance-charge" data-bs-toggle="modal"  style="text-decoration:none"> on views/inv/view.php
 * Related logic: see InvController/save_inv_allowance_charge
 * Related logic: see echo $modal_add_allowance_charge; at BOTTOM resources/views/invoice/inv/view.php
 * @var string $modal_add_allowance_charge_form
 * @var string $type
 */

?>
<?= Html::openTag('div', [
    'id' => 'add-inv-allowance-charge',
    'class' => 'modal',
    'tab-index' => '-1']); ?>
    <?= Html::openTag('div', ['class' => 'modal-dialog modal-lg']); ?>
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
                <?= $modal_add_allowance_charge_form; ?>    
            <?= Html::closeTag('div'); ?>
        <?= Html::closeTag('div'); ?>
    <?= Html::closeTag('div'); ?>
<?= Html::closeTag('div'); ?>
   