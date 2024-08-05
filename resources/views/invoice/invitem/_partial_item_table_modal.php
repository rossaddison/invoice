<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;

/**
 * @see Invoice...View...{select invoice}...Options dropdown button
 * @see InvController function view and function view_modal_delete_items
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $invItems
 */

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
        <?php
            /**
             * @var App\Invoice\Entity\InvItem $invItem
             */
            foreach ($invItems as $invItem) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="item_ids[]" value="<?php echo $invItem->getId();?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($invItem->getId()); ?></b>
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($invItem->getProduct() ? $invItem->getProduct()?->getProduct_sku() : ''); ?></b>
                </td>
                <td>
                    <b><?= Html::encode($invItem->getProduct() ? ($invItem->getProduct()?->getProduct_name() ?? '')
                                     : ($invItem->getTask()    ? ($invItem->getTask()?->getName() ?? ''): '')); ?></b>
                </td>
                <td>
                    <?= nl2br(Html::encode($invItem->getProduct() ? $invItem->getProduct()?->getProduct_description() 
                                        : ($invItem->getTask()    ? $invItem->getTask()?->getDescription() : ''))); ?>
                </td>
                <td class="text-right">
                    <?= $numberHelper->format_currency($invItem->getProduct() ? ($invItem->getProduct()?->getProduct_price() ?? 999.99) 
                                                    : ($invItem->getTask()    ? ($invItem->getTask()?->getPrice() ?? 999.99): '')); ?>
                </td>
                <td class="text-right">
                    <?= $invItem->getQuantity(); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
