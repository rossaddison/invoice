<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * Related logic: see QuoteController function view $parameters['model_delete_items']
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Task\TaskRepository $taskR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $quoteItems
 */

?>
<div class="table-responsive">
    <table class="table table-hover table-bordered table-striped">
        <tr>
            <th>&nbsp;</th>
            <th><?= $translator->translate('item'); ?></th>
            <th><?= $translator->translate('product.sku') . ' / ' . $translator->translate('task') . ' ' . $translator->translate('status'); ?></th>            
            <th><?= $translator->translate('product.name') . ' / ' . $translator->translate('task.name'); ?></th>
            <th><?= $translator->translate('product.description') . ' / ' . $translator->translate('task.description'); ?></th>
            <th class="text-right"><?= $translator->translate('product.price'); ?></th>
            <th class="text-right"><?= $translator->translate('quantity'); ?></th>
        </tr>
        <?php
            /**
             * @var App\Invoice\Entity\QuoteItem $quoteItem
             */
            foreach ($quoteItems as $quoteItem) { ?>
            <tr class="product">
                <td class="text-left">
                    <input type="checkbox" name="item_ids[]" value="<?php echo $quoteItem->getId();?>">
                </td>
                <td nowrap class="text-left">
                    <b><?= Html::encode($quoteItem->getId()); ?></b>
                </td>
                <?php if ($quoteItem->getProduct_id() > 0) { ?>
                    <td nowrap class="text-left">
                        <b><?= Html::encode($quoteItem->getProduct()?->getProduct_sku()); ?></b>
                    </td>
                    <td>
                        <b><?= Html::encode($quoteItem->getProduct()?->getProduct_name()); ?></b>
                    </td>
                    <td>
                        <?= nl2br(Html::encode($quoteItem->getProduct()?->getProduct_description())); ?>
                    </td>
                    <td class="text-right">
                        <?= $numberHelper->format_currency($quoteItem->getProduct()?->getProduct_price()); ?>
                    </td>
                <?php } ?>
                <?php if ($quoteItem->getTask_id() > 0) {
                    $taskStatuses = $taskR->getTaskStatuses($translator);
                    $taskStatus = (array) $taskStatuses[(string) $quoteItem->getTask()?->getStatus()];
                    $taskStatusLabel = (string) $taskStatus['label'];
                    ?>
                    <td nowrap class="text-left">
                        <b><?= Html::encode($taskStatusLabel); ?></b>
                    </td>
                    <td>
                        <b><?= Html::encode($quoteItem->getTask()?->getName()); ?></b>
                    </td>
                    <td>
                        <?= nl2br(Html::encode($quoteItem->getTask()?->getDescription())); ?>
                    </td>
                    <td class="text-right">
                        <?= $numberHelper->format_currency($quoteItem->getTask()?->getPrice()); ?>
                    </td>
                <?php } ?>    
                <td class="text-right">
                    <?= $quoteItem->getQuantity(); ?>
                </td>
            </tr>
        <?php } ?>

    </table>
</div>
