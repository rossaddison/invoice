<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/*
 * Related logic: see Invoice...View...{select invoice}...Options dropdown button
 * Related logic: see InvController function view and function view_modal_delete_items
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $invItems
 */

?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?php echo $translator->translate('item'); ?></th>
            <th><?php echo $translator->translate('product.sku'); ?></th>            
            <th><?php echo $translator->translate('product.name'); ?></th>
            <th><?php echo $translator->translate('product.description'); ?></th>
            <th class="text-right"><?php echo $translator->translate('product.price'); ?></th>
            <th class="text-right"><?php echo $translator->translate('quantity'); ?></th>
        </tr>
        <?php
            /**
             * @var App\Invoice\Entity\InvItem $invItem
             */
            foreach ($invItems as $invItem) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="item_ids[]" value="<?php echo $invItem->getId(); ?>">
                </td>
                <td nowrap class="text-left">
                    <b><?php echo Html::encode($invItem->getId()); ?></b>
                </td>
                <td nowrap class="text-left">
                    <b><?php echo Html::encode($invItem->getProduct() ? $invItem->getProduct()?->getProduct_sku() : ''); ?></b>
                </td>
                <td>
                    <b><?php echo Html::encode($invItem->getProduct() ? ($invItem->getProduct()?->getProduct_name() ?? '')
                                     : ($invItem->getTask() ? ($invItem->getTask()?->getName() ?? '') : '')); ?></b>
                </td>
                <td>
                    <?php echo nl2br(Html::encode($invItem->getProduct() ? $invItem->getProduct()?->getProduct_description()
                                        : ($invItem->getTask() ? $invItem->getTask()?->getDescription() : ''))); ?>
                </td>
                <td class="text-right">
                    <?php echo $numberHelper->format_currency($invItem->getProduct() ? ($invItem->getProduct()?->getProduct_price() ?? 999.99)
                                                    : ($invItem->getTask() ? ($invItem->getTask()?->getPrice() ?? 999.99) : '')); ?>
                </td>
                <td class="text-right">
                    <?php echo $invItem->getQuantity(); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
