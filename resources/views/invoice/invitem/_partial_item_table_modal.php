<?php
declare(strict_types=1); 
use Yiisoft\Html\Html;
// Used under Options to delete items
?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('i.item'); ?></th>
            <th><?= $translator->translate('i.product_sku'); ?></th>            
            <th><?= $translator->translate('i.product_name'); ?></th>
            <th><?= $translator->translate('i.product_description'); ?></th>
            <th class="text-right"><?= $translator->translate('i.product_price'); ?></th>
            <th class="text-right"><?= $translator->translate('i.quantity'); ?></th>
        </tr>
        <?php foreach ($invitems as $invitem) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="item_ids[]" value="<?php echo $invitem->getId();?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($invitem->getId()); ?></b>
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($invitem->getProduct() ? $invitem->getProduct()->getProduct_sku() : ''); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($invitem->getProduct() ? $invitem->getProduct()->getProduct_name() : ($invitem->getTask() ? $invitem->getTask()->getName() : '')); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($invitem->getProduct() ? $invitem->getProduct()->getProduct_description() : ($invitem->getTask() ? $invitem->getTask()->getDescription() : ''))); ?>
                </td>
                <td class="text-right">
                    <?= $numberhelper->format_currency($invitem->getProduct() ? $invitem->getProduct()->getProduct_price() : ($invitem->getTask() ? $invitem->getTask()->getPrice() : '')); ?>
                </td>
                <td class="text-right">
                    <?= $invitem->getQuantity(); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
