<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $products
 */

?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('product.sku'); ?></th>
            <th><?= $translator->translate('family.name'); ?></th>
            <th><?= $translator->translate('product.name'); ?></th>
            <th><?= $translator->translate('product.description'); ?></th>
            <th class="text-right"><?= $translator->translate('product.price'); ?></th>
        </tr>
        <?php
            /**
             * @var App\Invoice\Entity\Product $product
             */
            foreach ($products as $product) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="product_ids[]"
                           value="<?php echo (int)$product->getProduct_id(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($product->getProduct_sku()); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($product->getFamily()?->getFamily_name()); ?></b>
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
