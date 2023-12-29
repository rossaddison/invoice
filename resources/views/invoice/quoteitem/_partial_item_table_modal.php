<?php
declare(strict_types=1); 
use Yiisoft\Html\Html;
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
        <?php foreach ($quoteitems as $quoteitem) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="item_ids[]" value="<?php echo $quoteitem->getId();?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($quoteitem->getId()); ?></b>
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($quoteitem->getProduct()->getProduct_sku()); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($quoteitem->getProduct()->getProduct_name()); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($quoteitem->getProduct()->getProduct_description())); ?>
                </td>
                <td class="text-right">
                    <?= $numberhelper->format_currency($quoteitem->getProduct()->getProduct_price()); ?>
                </td>
                <td class="text-right">
                    <?= $quoteitem->getQuantity(); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
