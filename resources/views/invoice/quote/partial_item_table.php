<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteAmount $quoteAmount
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var string $csrf
 * @var string $excluded
 * @var string $included
 * @var string $language
 * @var array $quoteItems
 * @var array $products
 * @var array $taxRates
 * @var array $quoteTaxRates
 * @var array $units
 * @var bool $invEdit
 */

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
                    <input type="hidden" name="quote_id" maxlength="7" size="7" value="<?= $quote->getId(); ?>">
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
                                foreach ($taxRates as $taxRate) { ?>
                                <option value="<?php echo $taxRate->getTaxRateId(); ?>">
                                    <?= $percent = $numberHelper->format_amount($taxRate->getTaxRatePercent());
                                    $name = Html::encode($taxRate->getTaxRateName());
                                    if ($percent >= 0.00 && null!==$percent && strlen($name) > 0) {
                                        $percent . '% - ' . $name;
                                    } else {
                                        '#%';
                                    } ?>
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
                                    foreach ($units as $unit) { ?>
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
                 * @var App\Invoice\Entity\QuoteItem $item
                 */
                foreach ($quoteItems as $item) { ?>
                <tbody class="item">
                <tr>
                    <td rowspan="2" class="td-icon" style="text-align: center; vertical-align: middle;">
                        <i class="fa fa-arrows"></i>
                        <h5><bold><?= " ".(string)$count; ?></bold></h5>                       
                    </td>
                    <td class="td-text">
                        <div class="input-group">
                            <input type="text" disabled="true" maxlength="1" size="1" name="quote_id" value="<?= $item->getQuote_id(); ?>" data-bs-toggle = "tooltip" title="quote_item->quote_id">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_id" value="<?= $item->getId(); ?>" data-bs-toggle = "tooltip" title="quote_item->getId()">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_product_id" value="<?= $item->getProduct_id(); ?>" data-bs-toggle = "tooltip" title="quote_item->product_id">
                        </div>    
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                            <select name="item_name" class="form-control" disabled>                                
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\Product $product
                                     */
                                    foreach ($products as $product) { ?>
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
                            <input disabled type="text" name="item_quantity" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->quantity"
                                   value="<?= $numberHelper->format_amount($item->getQuantity()); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                            <input disabled type="text" name="item_price" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->price"
                                   value="<?= $numberHelper->format_amount($item->getPrice()); ?>">
                        </div>
                    </td>
                    <td class="td-amount ">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.item_discount'); ?></span>
                            <input disabled type="text" name="item_discount_amount" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="quote_item->discount_amount"
                                   value="<?= $numberHelper->format_amount($item->getDiscount_amount()); ?>"
                                   data-bs-toggle = "tooltip" data-placement="bottom"
                                   title="<?= $s->getSetting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>">
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></span>
                            <select disabled name="item_tax_rate_id" class="form-control" data-bs-toggle = "tooltip" title="quote_item->tax_rate_id">
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\TaxRate $taxRate
                                     */
                                    foreach ($taxRates as $taxRate) { ?>
                                    <option value="<?php echo $taxRate->getTaxRateId(); ?>"
                                        <?php if ($item->getTax_rate_id() == $taxRate->getTaxRateId()) { ?>selected="selected"<?php } ?>>
                                        <?= $percent = $numberHelper->format_amount($taxRate->getTaxRatePercent());
                                            $name = Html::encode($taxRate->getTaxRateName());
                                            if ($percent >= 0.00 && null!==$percent && strlen($name) > 0) {
                                                $percent . '% - ' . $name;
                                            } else {
                                                '#%';
                                            } ?>
                                    </option>
                                    <?php } ?>
                            </select>
                        </div>
                    </td>
                    <td class="td-icon text-right td-vert-middle">
                        
                    <?php if ($invEdit) { ?>
                        <span data-bs-toggle="tooltip" title="<?= $translator->translate('invoice.productimage.gallery'). (string)$item->getProduct()?->getProduct_name(); ?>">
                            <a class="btn btn-info fa fa-eye" data-bs-toggle="modal" href="#view-product-<?= $item->getId(); ?>" style="text-decoration:none"></a></span> 
                            <div id="view-product-<?= $item->getId(); ?>" class="modal modal-lg" tabindex="-1" role="dialog" aria-labelledby="modal_view_product_<?= $item->getId(); ?>" aria-hidden="true">
                                <div class="modal-content">
                                    <div class="modal-header">
                                      <button type="button" class="close" data-bs-dismiss"modal"><i class="fa fa-times-circle"></i></button>
                                    </div>    
                                    <div>
                                      <?php $productImages = $piR->repoProductImageProductquery((int)$item->getProduct_id()); ?>
                                      <?php
                                       /**
                                        * @var App\Invoice\Entity\ProductImage $productImage
                                        */
                                       foreach ($productImages as $productImage) { ?>
                                       <?php if (!empty($productImage->getFile_name_original())) { ?> 
                                          <a data-bs-toggle="modal" class="col-sm-4">
                                             <img src="<?= '/products/'. $productImage->getFile_name_original(); ?>"  class="img-fluid">
                                          </a>
                                       <?php } ?> 
                                      <?php } ?>
                                    </div>
                                    <div class="modal-footer">
                                    </div>  
                                </div> 
                            </div><a href="<?= $urlGenerator->generate('quote/delete_quote_item',['_language' => $language, 'id'=>$item->getId()]) ?>" class="btn btn-danger" onclick="return confirm('<?= $translator->translate('i.delete_record_warning'); ?>');"><i class="fa fa-trash"></i></a>
                        <a href="<?= $urlGenerator->generate('quoteitem/edit',['_language' => $language, 'id'=>$item->getId()]) ?>" class="btn btn-success"><i class="fa fa-pencil"></i></a>
                    </td>
                    <?php } ?>
                </tr>
                <tr>
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text" data-bs-toggle = "tooltip" title="quote_item->description"><?= $translator->translate('i.description'); ?></span>
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
                        <span name="subtotal" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->subtotal">
                            <?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery((int)$item->getId())?->getSubtotal() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.item_discount') : $translator->translate('invoice.invoice.cash.discount'); ?></span>
                        <span name="item_discount_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->discount">
                            <?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery((int)$item->getId())?->getDiscount() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $vat === '0' ? $translator->translate('i.tax') : $translator->translate('invoice.invoice.vat.abbreviation') ?></span><br/>
                        <span name="item_tax_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->tax_total">
                            <?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery((int)$item->getId())?->getTax_total() ?? 0.00); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.total'); ?></span><br/>
                        <span name="item_total" class="amount" data-bs-toggle = "tooltip" title="quote_item_amount->total">
                            <?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery((int)$item->getId())?->getTotal() ?? 0.00); ?>
                        </span>
                    </td>                   
                </tr>
                </tbody>
            <?php $count = $count + 1;} ?> 
        </table>
    </div>
    <br>
    <?php 
        /***********************/
        /*   Totals start here */
        /***********************/
    ?> 
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="col-xs-12 col-md-4" quote_tax_rates="<?php $quoteTaxRates; ?>"></div>
        <div class="col-xs-12 visible-xs visible-sm"><br></div>
        <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
            <table class="table table-bordered text-right">
                <tr>
                    <td style="width: 40%;"><?= $translator->translate('i.subtotal'); ?></td>
                    <td style="width: 60%;" class="amount" id="amount_subtotal" data-bs-toggle = "tooltip" title="quote_amount->item_subtotal =  quote_item(s)->subtotal - quote_item(s)->discount"><?php echo $numberHelper->format_currency($quoteAmount->getItem_subtotal() ?? 0.00); ?></td>
                </tr>
                <tr>
                    <td>
                    <span><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax'); ?>
                    </span>    
                    </td>
                    <td class="amount" data-bs-toggle = "tooltip" id="amount_item_tax_total" title="quote_amount->item_tax_total"><?php echo $numberHelper->format_currency($quoteAmount->getItem_tax_total() ?? 0.00); ?></td>
                </tr>
                <?php if ($vat === '0') { ?>
                <tr>
                    <td>
                        <?php if ($invEdit) { ?>    
                            <a href="#add-quote-tax" data-bs-toggle="modal" class="btn-xs"><i class="fa fa-plus-circle"></i></a>
                        <?php } ?>
                        <span><?= $translator->translate('i.quote_tax_rate'); ?></span>
                    </td>
                    <td>
                        <?php if ($quoteTaxRates) {
                            /**
                             * @var App\Invoice\Entity\QuoteTaxRate $quoteTaxRate
                             */
                            foreach ($quoteTaxRates as $quoteTaxRate) { ?>
                            <div data-bs-toggle="tooltip" title="<?= $quoteTaxRate->getInclude_item_tax() == '1' ? $included : $excluded; ?>"> 
                                <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                <?php if ($invEdit) { ?>
                                <span type="submit" class="btn btn-xs btn-link" onclick="return confirm('<?= $translator->translate('i.delete_tax_warning'); ?>');">
                                    <a href="<?= $urlGenerator->generate('quote/delete_quote_tax_rate',['id'=>$quoteTaxRate->getId()]) ?>"><i class="fa fa-trash"></i></a>
                                </span>
                                <?php } ?>
                                <span class="text-muted">
                                    <?= $percent = $numberHelper->format_amount($quoteTaxRate->getTaxRate()?->getTaxRatePercent());
                                    $name = Html::encode($quoteTaxRate->getTaxRate()?->getTaxRateName());
                                    if ($percent >= 0.00 && null!==$percent && strlen($name) > 0) {
                                          $name .' '. $percent. '%';
                                    } else {
                                        '#%';
                                    } ?>
                                </span>
                                <span class="amount" data-bs-toggle = "tooltip" title="quote_tax_rate->quote_tax_rate_amount">
                                    <?php echo $numberHelper->format_currency($quoteTaxRate->getQuote_tax_rate_amount()); ?>
                                </span>
                            </div>        
                            <?php }
                        } else {
                            echo $numberHelper->format_currency('0');
                        } ?>
                    </td>
                </tr>
                <?php } ?>
                <?php if ($vat === '0') { ?>
                <tr>
                    <td class="td-vert-middle"><?= $translator->translate('i.discount'); ?></td>
                    <td class="clearfix">
                        <div class="discount-field">
                            <div class="input-group input-group-sm">
                                <input id="quote_discount_amount" name="quote_discount_amount"
                                       class="discount-option form-control input-sm amount" data-bs-toggle = "tooltip" title="quote->discount_amount" disabled
                                       value="<?= $numberHelper->format_amount($quote->getDiscount_amount() != 0 ? $quote->getDiscount_amount() : 0.00); ?>">
                                <div
                                    class="input-group-text"><?= $s->getSetting('currency_symbol'); ?>
                                </div>
                            </div>
                        </div>
                        <div class="discount-field">
                            <div class="input-group input-group-sm">
                                <input id="quote_discount_percent" name="quote_discount_percent" data-bs-toggle = "tooltip" title="quote->discount_percent" disabled
                                       value="<?= $numberHelper->format_amount($quote->getDiscount_percent() != 0 ? $quote->getDiscount_percent() : 0.00); ?>"
                                       class="discount-option form-control input-sm amount">
                                <div class="input-group-text">&percnt;</div>
                            </div>
                        </div>
                    </td>
                </tr>                
                <?php } ?>
                <tr>
                    <td><b><?= $translator->translate('i.total'); ?></b></td>
                    <td class="amount" id="amount_quote_total" data-bs-toggle = "tooltip" title="quote_amount->total"><b><?php echo $numberHelper->format_currency($quoteAmount->getTotal() ?? 0.00); ?></b></td>
                </tr>
            </table>
        </div>
    </div>
    <hr>