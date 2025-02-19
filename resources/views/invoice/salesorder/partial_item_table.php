<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Group\GroupRepository $gR
 * @var App\Invoice\Product\ProductRepository $pR
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\TaxRate\TaxRateRepository $trR
 * @var App\Invoice\Unit\UnitRepository $uR
 * @var App\Invoice\Entity\SalesOrder $so
 * @var App\Invoice\Entity\SalesOrderAmount $so_amount
 * @var App\Invoice\Entity\SalesOrderTaxRate $soTaxRates
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $soItems
 * @var string $csrf
 * @var bool $invEdit
 * @var bool $invView
 * */

$vat = $s->getSetting('enable_vat_registration');
?>

<div class="table-striped table-responsive">
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
            
            <?php
            //**********************************************************************************************
            // New 
            //**********************************************************************************************
            ?>

            <tbody id="new_row" style="display: none;">
            <tr>
                <td rowspan="2" class="td-icon" style="text-align: center; vertical-align: middle;"><i class="fa fa-arrows"></i></td>
                <td class="td-text">
                    <input type="hidden" name="quote_id" maxlength="7" size="7" value="<?php echo $so->getId(); ?>">
                    <input type="hidden" name="item_id" maxlength="7" size="7" value="">
                    <input type="hidden" name="item_product_id" maxlength="7" size="7" value="">

                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                        <input type="text" name="item_name" class="input-sm form-control" value="" disabled>
                    </div>
                </td>
                <td class="td-amount td-quantity">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                        <input type="text" name="item_quantity" class="input-sm form-control amount" value="1.00">
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                        <input type="text" name="item_price" class="input-sm form-control amount" value="0.00">
                    </div>
                </td>
                <td class="td-amount td-vert-middle">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.item_discount'); ?></span>
                        <input type="text" name="item_discount_amount" class="input-sm form-control amount"
                               data-bs-toggle = "tooltip" data-placement="bottom"
                               title="<?= $s->getSetting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>" value="0.00">
                    </div>
                </td>
                <td td-vert-middle>
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.tax_rate'); ?></span>
                        <select name="item_tax_rate_id" class="form-control">
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                            <?php
                                /**
                                 * @var App\Invoice\Entity\TaxRate $taxRate
                                 */
                                foreach ($trR->findAllPreloaded() as $taxRate) { ?>
                                <option value="<?php echo $taxRate->getTaxRateId(); ?>">
                                    <?php
                                        $taxRatePercent = $taxRate->getTaxRatePercent();
                                        $taxRateName = $taxRate->getTaxRateName();
                                        if (null!==$taxRatePercent && null!==$taxRateName) {
                                            echo $numberHelper->format_amount((string)$taxRatePercent . '% - ' . $taxRateName);
                                        }; ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </td>
                <td class="td-icon text-right td-vert-middle">
                    <form method="POST" class="form-inline">
                            <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                            <button type="submit" class="btn_delete_item btn-xl btn-primary" onclick="return confirm('<?= $translator->translate('i.delete_record_warning'); ?>');">
                                <i class="fa fa-trash"></i>
                            </button>
                    </form>
                </td>
            </tr>
            <tr>
                <td class="td-textarea">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.description'); ?></span>
                        <textarea name="item_description" class="form-control"></textarea>
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.product_unit'); ?></span>
                            <select name="item_product_unit_id" class="form-control" disabled>
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Unit $unit
                                     */
                                    foreach ($uR->findAllPreloaded() as $unit) { ?>
                                    <option value="<?= $unit->getUnit_id(); ?>">
                                        <?= Html::encode($unit->getUnit_name()) . "/" . Html::encode($unit->getUnit_name_plrl()); ?>
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
                //*************************************************************************************
                // Current 
                // ************************************************************************************
                $count = 1;
                /**
                 * @var App\Invoice\Entity\SalesOrderItem $item
                 */
                
                foreach ($soItems as $item) { ?>
                <tbody class="item">
                <tr>
                    <td rowspan="2" class="td-icon" style="text-align: center; vertical-align: middle;">
                        <i class="fa fa-arrows"></i>
                        <h5><bold><?= " ".(string)$count; ?></bold></h5>                       
                    </td>
                    <td class="td-text">
                        <div class="input-group">
                            <input type="text" disabled="true" maxlength="1" size="1" name="so_id" value="<?= $item->getSales_order_id(); ?>" data-bs-toggle = "tooltip" title="salesorder_item->quote_id">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_id" value="<?= $item->getId(); ?>" data-bs-toggle = "tooltip" title="salesorder_item->getId()">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_product_id" value="<?= $item->getProduct_id(); ?>" data-bs-toggle = "tooltip" title="salesorder_item->product_id">
                            <input type="text" disabled="true" placeholder="Peppol" maxlength="8" size="8" name="item_peppol_po_itemid" value="<?= $item->getPeppol_po_itemid(); ?>" data-bs-toggle = "tooltip" title="salesorder_item->peppol_po_itemid This value is editable if the client or customer is going to pay by Peppol. They have to supply their corresponding Purchase Order Item Id here. https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/cbc-ID/">
                            <input type="text" disabled="true" placeholder="Peppol" maxlength="8" size="8" name="item_peppol_po_lineid" value="<?= $item->getPeppol_po_lineid(); ?>" data-bs-toggle = "tooltip" title="salesorder_item->peppol_po_lineid This value is editable if the client or customer is going to pay by Peppol. They have to supply their corresponding Purchase Order Line Number here. https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/cac-InvoiceLine/cac-OrderLineReference/cbc-LineID/">
                        </div>    
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                            <select name="item_name" class="form-control" disabled>                                
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Product $product
                                     */
                                    foreach ($pR->findAllPreloaded() as $product) { ?>
                                    <option value="<?php echo $product->getProduct_id(); ?>"
                                            <?php if ($item->getProduct_id() == $product->getProduct_id()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $product->getProduct_name(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-amount td-quantity">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                            <input disabled type="text" name="item_quantity" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="salesorder_item->quantity"
                                   value="<?= $numberHelper->format_amount($item->getQuantity()); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                            <input disabled type="text" name="item_price" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="salesorder_item->price"
                                   value="<?= $numberHelper->format_amount($item->getPrice()); ?>">
                        </div>
                    </td>
                    <td class="td-amount ">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item_discount'); ?></span>
                            <input disabled type="text" name="item_discount_amount" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="salesorder_item->discount_amount"
                                   value="<?= $numberHelper->format_amount($item->getDiscount_amount()); ?>"
                                   data-bs-toggle = "tooltip" data-placement="bottom"
                                   title="<?= $s->getSetting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>">
                        </div>
                    </td>                    
                    <td>
                        <div class="input-group">
                            <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></span>
                            <select disabled name="item_tax_rate_id" class="form-control" data-bs-toggle = "tooltip" title="salesorder_item->tax_rate_id">
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($trR->findAllPreloaded() as $taxRate) { ?>
                                    <option value="<?php echo $taxRate->getTaxRateId(); ?>"
                                        <?php
                                            $taxRatePercent = $taxRate->getTaxRatePercent();
                                            $taxRatePercentNumber = $numberHelper->format_amount($taxRatePercent);
                                            $taxRateName = $taxRate->getTaxRateName();
                                            if ($item->getTax_rate_id() == $taxRate->getTaxRateId()) { ?>selected="selected"<?php } ?>>
                                        <?php
                                            if (null!==$taxRatePercentNumber && null!==$taxRateName) {
                                                echo  Html::encode($taxRatePercentNumber. '% - ' . $taxRateName); 
                                            }
                                        ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-icon text-right td-vert-middle">
                    <?php if ($invEdit || $invView) { ?>    
                        <a href="<?= $urlGenerator->generate('salesorderitem/edit',['id'=>$item->getId()]) ?>" class="btn btn-md btn-link"><i class="fa fa-pencil"></i></a>
                    </td>
                    <?php } ?>
                </tr>
                <tr>
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text" data-bs-toggle = "tooltip" title="salesorder_item->description"><?= $translator->translate('i.description'); ?></span>
                            <textarea disabled name="item_description" class="form-control" ><?= Html::encode($item->getDescription()); ?></textarea>
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.product_unit');?></span>
                            <span class="input-group-text" name="item_product_unit"><?= $item->getProduct_unit();?></span>
                        </div>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.subtotal'); ?></span><br/>                        
                        <span name="subtotal" class="amount" data-bs-toggle = "tooltip" title="salesorder_item_amount->subtotal">
                            <?= $numberHelper->format_currency($soiaR->repoSalesOrderItemAmountquery($item->getId())?->getSubtotal() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.item_discount') : $translator->translate('invoice.invoice.cash.discount'); ?></span>
                        <span name="item_discount_total" class="amount" data-bs-toggle = "tooltip" title="salesorder_item_amount->discount">
                            <?= $numberHelper->format_currency($soiaR->repoSalesOrderItemAmountquery($item->getId())?->getDiscount() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $vat === '0' ? $translator->translate('i.tax') : $translator->translate('invoice.invoice.vat.abbreviation') ?></span><br/>
                        <span name="item_tax_total" class="amount" data-bs-toggle = "tooltip" title="salesorder_item_amount->tax_total">
                            <?= $numberHelper->format_currency($soiaR->repoSalesOrderItemAmountquery($item->getId())?->getTax_total() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.total'); ?></span><br/>
                        <span name="item_total" class="amount" data-bs-toggle = "tooltip" title="salesorder_item_amount->total">
                            <?= $numberHelper->format_currency($soiaR->repoSalesOrderItemAmountquery($item->getId())?->getTotal() ?? 0.00); ?>
                        </span>
                    </td>                   
                </tr>
                </tbody>
            <?php $count = $count + 1;} ?> 
        </table>
    </div>
     <br>
     
    <div class='row'>
        <div class="col-xs-12 col-md-4" quote_tax_rates="<?php $soTaxRates; ?>">
           
        </div>
        <div class="col-xs-12 visible-xs visible-sm"><br></div>

        <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
            <table class="table table-bordered text-right">
                <tr>
                    <td style="width: 40%;"><?= $translator->translate('i.subtotal'); ?></td>
                    <td style="width: 60%;" class="amount" id="amount_subtotal" data-bs-toggle = "tooltip" title="salesorder_amount->item_subtotal =  salesorder_item(s)->subtotal - salesorder_item(s)->discount"><?php echo $numberHelper->format_currency($so_amount->getItem_subtotal() ?? 0.00); ?></td>
                </tr>
                <tr>
                    <td>
                    <span><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax'); ?>
                    </span>    
                    </td>
                    <td class="amount" data-bs-toggle = "tooltip" id="amount_item_tax_total" title="quote_amount->item_tax_total"><?php echo $numberHelper->format_currency($so_amount->getItem_tax_total() ?? 0.00); ?></td>
                </tr>
                <?php if ($vat === '0') { ?>
                <tr>
                    <td>
                        <?php if ($invEdit) { ?>    
                            <a href="#add-quote-tax" data-bs-toggle="modal" class="btn-xs"><i class="fa fa-plus-circle"></i></a>
                        <?php } ?>
                        <span>$translator->translate('i.quote_tax_rate'); ?></span>
                    </td>                    
                </tr>
                <?php } ?>
                <?php if ($vat == (string)0) { ?>
                <tr>
                    <td class="td-vert-middle"><?= $translator->translate('i.discount'); ?></td>
                    <td class="clearfix">
                        <div class="discount-field">
                            <div class="input-group input-group-sm">
                                <input id="quote_discount_amount" name="quote_discount_amount"
                                       class="discount-option form-control input-sm amount" data-bs-toggle = "tooltip" title="quote->discount_amount" disabled
                                       value="<?= $numberHelper->format_amount($so->getDiscount_amount() != 0 ? $so->getDiscount_amount() : 0.00); ?>">
                                <div
                                    class="input-group-text"><?= $s->getSetting('currency_symbol'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="discount-field">
                            <div class="input-group input-group-sm">
                                <input id="quote_discount_percent" name="quote_discount_percent" data-bs-toggle = "tooltip" title="quote->discount_percent" disabled
                                       value="<?= $numberHelper->format_amount($so->getDiscount_percent() != 0 ? $so->getDiscount_percent() : 0.00); ?>"
                                       class="discount-option form-control input-sm amount">
                                <div class="input-group-text">&percnt;</div>
                            </div>
                        </div>
                    </td>
                </tr>                
                <?php } ?>
                <tr>
                    <td><b><?= $translator->translate('i.total'); ?></b></td>
                    <td class="amount" id="amount_quote_total" data-bs-toggle = "tooltip" title="quote_amount->total"><b><?php echo $numberHelper->format_currency($so_amount->getTotal() ?? 0.00); ?></b></td>
                </tr>
            </table>
        </div>
    </div>
    <hr>