<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

?>
<div class="table-striped">
        <table id="item_table" class="items table-primary table table-bordered no-margin">
            <thead style="display: none">
            <tr>
                <th></th>
                <th><?= $translator->translate('i.item'); ?></th>
                <th><?= $translator->translate('i.description'); ?></th>
                <th><?= $translator->translate('i.quantity'); ?></th>
                <th><?= $translator->translate('i.price'); ?></th>
                <th><?= $translator->translate('i.tax_rate'); ?></th>
                <th><?= $translator->translate('i.subtotal'); ?></th>
                <th><?= $translator->translate('i.tax'); ?></th>
                <th><?= $translator->translate('i.total'); ?></th>
                <th></th>
            </tr>
            </thead>

            <tbody id="new_row" style="display: none;">
            <tr>
                <td rowspan="2" class="td-icon"><i class="fa fa-arrows cursor-move"></i></td>
                <td class="td-text">
                    <input type="hidden" name="quote_id" value="<?php echo $quote->getId(); ?>">
                    <input type="hidden" name="item_id" value="">
                    <input type="hidden" name="item_product_id" value="">

                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                        <input type="text" name="item_name" class="input-sm form-control" value="">
                    </div>
                </td>
                <td class="td-amount td-quantity">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                        <input type="text" name="item_quantity" class="input-sm form-control amount" value="">
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                        <input type="text" name="item_price" class="input-sm form-control amount" value="">
                    </div>
                </td>
                <td class="td-amount td-vert-middle">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.item_discount'); ?></span>
                        <input type="text" name="item_discount_amount" class="input-sm form-control amount"
                               data-bs-toggle = "tooltip" data-placement="bottom"
                               title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>">
                    </div>
                </td>
                <td td-vert-middle>
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.tax_rate'); ?></span>
                        <select name="item_tax_rate_id" class="form-control input-sm">
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                            <?php foreach ($tax_rates as $tax_rate) { ?>
                                <option value="<?php echo $tax_rate->getId(); ?>">
                                    <?php echo $numberhelper->format_amount($tax_rate->tax_rate_percent) . '% - ' . $tax_rate->tax_rate_name; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </td>
                <td class="td-icon text-right td-vert-middle">
                    <button type="button" class="btn_delete_item btn btn-link btn-sm" title="<?= $translator->translate('i.delete'); ?>">
                        <i class="fa fa-trash text-danger"></i>
                    </button>
                </td>
            </tr>
            <tr>
                <td class="td-textarea">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.description'); ?></span>
                        <textarea name="item_description" class="input-sm form-contro text-center vertical"></textarea>
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.product_unit'); ?></span>
                        <select name="item_product_unit_id" 
                                class="form-control input-sm">
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                            <?php foreach ($units as $unit) { ?>
                                <option value="<?php echo $unit->getId(); ?>">
                                    <?= Html::encode($unit->unit_name) . "/" . Html::encode($unit->unit_name_plrl); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </td>
                <td class="td-amount td-vert-middle">
                    <span><?= $translator->translate('i.subtotal'); ?></span><br/>
                    <span name="subtotal" class="amount"></span>
                </td>
                <td class="td-amount td-vert-middle">
                    <span><?= $translator->translate('i.discount'); ?></span><br/>
                    <span name="item_discount_total" class="amount"></span>
                </td>
                <td class="td-amount td-vert-middle">
                    <span><?= $translator->translate('i.tax'); ?></span><br/>
                    <span name="item_tax_total" class="amount"></span>
                </td>
                <td class="td-amount td-vert-middle">
                    <span><?= $translator->translate('i.total'); ?></span><br/>
                    <span name="item_total" class="amount"></span>
                </td>
            </tr>
            </tbody>

            <?php
               //quote items
               foreach ($quote_items as $item) { ?>
                <tbody class="item">
                <tr>
                    <td rowspan="2" class="td-icon"><i class="fa fa-arrows"></i></td>
                    <td class="td-text">
                        <input type="hidden" name="quote_id" value="<?= $item->quote_id; ?>" data-bs-toggle = "tooltip" title="quote_item->quote_id">
                        <input type="hidden" name="item_id" value="<?= $item->getId(); ?>" data-bs-toggle = "tooltip" title="quote_item->getId()">
                        <input type="hidden" name="item_product_id" value="<?= $item->product_id; ?>" data-bs-toggle = "tooltip" title="quote_item->product_id">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                            <select name="item_name" class="form-control">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($products as $product) { ?>
                                    <option value="<?php echo $product->getId(); ?>"
                                            <?php if ($item->product_id == $product->getId()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $product->product_name; ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-amount td-quantity">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                            <input type="text" name="item_quantity" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->quantity"
                                   value="<?= $numberhelper->format_amount($item->quantity); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                            <input type="text" name="item_price" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->price"
                                   value="<?= $numberhelper->format_amount($item->price); ?>">
                        </div>
                    </td>
                    <td class="td-amount ">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item_discount'); ?></span>
                            <input type="text" name="item_discount_amount" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->discount_amount"
                                   value="<?= $numberhelper->format_amount($item->discount_amount); ?>"
                                   data-bs-toggle = "tooltip" data-placement="bottom"
                                   title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>">
                        </div>
                    </td>
                    <td>
                    <?php
                       //get the percentage
                       $percentage = '';
                       foreach ($tax_rates as $tax_rate) {
                       if ($item->tax_rate_id == $tax_rate->getId()){
                          $percentage = $numberhelper->format_amount($tax_rate->tax_rate_percent) . '% - ' . Html::encode($tax_rate->tax_rate_name);
                       } 
                    }?>
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.tax_rate'); ?></span>
                            <select name="item_tax_rate_id" class="form-control" data-bs-toggle = "tooltip" title="quote_item->tax_rate_id">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?php echo $tax_rate->getId(); ?>"
                                            <?php if ($item->tax_rate_id == $tax_rate->getId()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $numberhelper->format_amount($tax_rate->tax_rate_percent) . '% - ' . Html::encode($tax_rate->tax_rate_name); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-icon text-right td-vert-middle">
                        <a href =""  data-id="<?= $item->getId(); ?>" class="btn_delete_item btn btn-link btn-sm" title="<?= $translator->translate('i.delete').' item '. $item->getId(); ?>" id="<?php $item->getId(); ?>"><i class="fa fa-trash text-danger"></i></a>
                    </td>
                </tr>
                <tr>
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text" data-bs-toggle = "tooltip" title="quote_item->description"><?= $translator->translate('i.description'); ?></span>
                            <textarea name="item_description" class="form-control" ><?= Html::encode($item->description); ?></textarea>
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.product_unit'); ?></span>
                            <select name="item_product_unit_id" class="form-control" data-bs-toggle = "tooltip" title="quote_item->product_unit_id">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($units as $unit) { ?>
                                    <option value="<?= $unit->getId(); ?>"
                                        <?php if ($item->product_unit_id == $unit->getId()) { ?>selected="selected"<?php } ?>>
                                        <?= $s->check_select($item->product_unit_id, $unit->getId()); ?>
                                        <?= Html::encode($unit->unit_name) . "/" . Html::encode($unit->unit_name_plrl); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.subtotal'); ?></span><br/>
                        
                        <span name="subtotal" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->subtotal">
                            <?= $numberhelper->format_currency($quote_item_amount->repoQuoteItemAmountquery((string)$item->getId())->subtotal); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.discount'); ?></span><br/>
                        <span name="item_discount_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->discount">
                            <?= $numberhelper->format_currency($quote_item_amount->repoQuoteItemAmountquery((string)$item->getId())->discount); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.tax'); ?></span><br/>
                        <span name="item_tax_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->tax_total">
                            <?= $numberhelper->format_currency($quote_item_amount->repoQuoteItemAmountquery((string)$item->getId())->tax_total); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.total'); ?></span><br/>
                        <span name="item_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->total">
                            <?= $numberhelper->format_currency($quote_item_amount->repoQuoteItemAmountquery((string)$item->getId())->total); ?>
                        </span>
                    </td>
                </tr>
                </tbody>
            <?php } ?>
        </table>
    </div>