<?php
declare(strict_types=1); 
use Yiisoft\Html\Html;
?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('i.product_sku'); ?></th>
            <th><?= $translator->translate('i.family_name'); ?></th>
            <th><?= $translator->translate('i.product_name'); ?></th>
            <th><?= $translator->translate('i.product_description'); ?></th>
            <th class="text-right"><?= $translator->translate('i.product_price'); ?></th>
        </tr>
        <?php foreach ($products as $product) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="product_ids[]"
                           value="<?php echo (integer)$product->getProduct_id(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($product->getProduct_sku()); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($product->getFamily()->getFamily_name()); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($product->getProduct_name()); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($product->getProduct_description())); ?>
                </td>
                <td class="text-right">
                    <?= $numberHelper->format_currency($product->getProduct_price()); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
