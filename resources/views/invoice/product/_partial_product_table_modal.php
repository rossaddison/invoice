<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $products
 */

?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?php echo $translator->translate('product.sku'); ?></th>
            <th><?php echo $translator->translate('family.name'); ?></th>
            <th><?php echo $translator->translate('product.name'); ?></th>
            <th><?php echo $translator->translate('product.description'); ?></th>
            <th class="text-right"><?php echo $translator->translate('product.price'); ?></th>
        </tr>
        <?php
            /**
             * @var App\Invoice\Entity\Product $product
             */
            foreach ($products as $product) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="product_ids[]"
                           value="<?php echo (int) $product->getProduct_id(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?php echo Html::encode($product->getProduct_sku()); ?></b>
                </td>
                <td>
                    <b><?php echo Html::encode($product->getFamily()?->getFamily_name()); ?></b>
                </td>
                <td>
                    <b><?php echo Html::encode($product->getProduct_name()); ?></b>
                </td>
                <td>
                    <?php echo nl2br(Html::encode($product->getProduct_description())); ?>
                </td>
                <td class="text-right">
                    <?php echo $numberHelper->format_currency($product->getProduct_price()); ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
