<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Entity\Quote $quote
 * @var App\Invoice\Entity\QuoteAmount $quoteAmount
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\QuoteItemAmount\QuoteItemAmountRepository $qiaR
 * @var App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository
 *      $acqiR
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var array $quoteItems
 * @var array $quoteTaxRates
 * @var array $products
 * @var array $tasks
 * @var array $taxRates
 * @var array $units
 * @var bool $draft
 * @var bool $invEdit
 * @var string $csrf
 * @var string $included
 * @var string $excluded
 */

$vat = $s->getSetting('enable_vat_registration');
?>

<div>
        <table id="item_table"
               class="items table table-responsive table-condensed
                      table-bordered no-margin">
            <thead>
            <tr><i class="fa fa-info-circle" 
                   data-bs-toggle="tooltip" 
                   title="<?= $s->isDebugMode(19); ?>"></i>
            </tr>    
            <tr>               
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
                <th></th>
            </tr>
            </thead>
           
<?php
//*********
// Current
// ********
$count = 1;
/**
 * @var App\Invoice\Entity\QuoteItem $item
 */
foreach ($quoteItems as $item) {
    $productId = $item->getProduct_id();
    $taskId = $item->getTask_id();
    $productRef = '';
    $taskRef = '';
    if ($productId > 0) {
        $productRef = A::tag()
           ->href($urlGenerator->generate('product/view',
                   [
                       '_language' => (string) $session->get('_language'),
                       'id' => $productId
                   ])
           )
           ->content($productId)
           ->render();
    }
    if ($taskId > 0) {
        $taskRef = A::tag()
           ->href($urlGenerator->generate('task/view',
                   [
                       '_language' => (string) $session->get('_language'),
                       'id' => $taskId]
                   )
           )
           ->content($taskId)
           ->render();
    }
    ?>
            <tbody class="item">
                <tr>
                    <td class="td-text" style="background-color: lightgreen">
                        <b>
                            <div class="input-group">
                                
<?php echo $count . '-' . $item->getQuote_id() . '-' . $item->getId() . '-'
    . ($productId > 0 ? $productRef : '') . ($taskId > 0 ? $taskRef : ''); ?>
                                
                            </div>
                        </b>
                    </td>                    
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text">
                                <b>
<?= $item->getProduct_id() > 0 ? $translator->translate('item') :
        $translator->translate('tasks') ; ?>
                                </b>
                            </span>
                            <select name="item_name"
                                    class="form-control"
                                    disabled>
<?php if ($item->getProduct_id() > 0) { ?>    
                                <option value="0">
<?= $translator->translate('none'); ?>
                                </option>
<?php
/**
 * @var App\Invoice\Entity\Product $product
 */
foreach ($products as $product) { ?>
                                <option value="
<?php echo $product->getProduct_id(); ?>"
    <?php if ($item->getProduct_id() == $product->getProduct_id()) { ?>
                                        selected="selected"
                                    <?php } ?>>
<?php echo $product->getProduct_name(); ?>
                                </option>
                                <?php } ?>
<?php } ?>
<?php if ($item->getTask_id() > 0) { ?>    
                                <option value="0">
<?= $translator->translate('none'); ?>
                                </option>
<?php
/**
 * @var App\Invoice\Entity\Task $task
 */
foreach ($tasks as $task) { ?>
                                <option value="
<?php echo $task->getId(); ?>"
    <?php if ($item->getTask_id() == $task->getId()) { ?>
                                        selected="selected"<?php } ?>>
                                        <?php echo $task->getName(); ?>
                                </option>
                                <?php } ?>
                            <?php } ?>        
                            </select>
                        </div>
                    </td>  
                    <td class="td-amount td-quantity">
                        <div class="input-group">
                            <span class="input-group-text">
                                <b>
                            <?= $translator->translate('quantity'); ?>
                                </b>
                            </span>
                            <input disabled type="text"
                                   maxlength="4"
                                   size="4"
                                   name="item_quantity"
                                   class="input-sm form-control amount"
                                   data-bs-toggle = "tooltip"
                                   title="quote_item->quantity"
                                   value="
<?= $numberHelper->format_amount($item->getQuantity()); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                      <div class="input-group">
                          <span class="input-group-text">
                              <b><?= $translator->translate('price'); ?></b>
                          </span>
                          <input disabled type="text"
                                 maxlength="4"
                                 size="4"
                                 name="item_price"
                                 class="input-sm form-control amount"
                                 data-bs-toggle = "tooltip"
                                 title="quote_item->price"
                                 value="
<?= $numberHelper->format_amount($item->getPrice()); ?>">
                      </div>
                    </td>
                    <td class="td-amount ">
                        <div class="input-group">
                            <span class="input-group-text">
                                <b>
<?= $vat === '0' ? $translator->translate('item.discount') :
    $translator->translate('cash.discount'); ?>
                                </b>
                            </span>
                            <input disabled type="text"
                                   maxlength="4"
                                   size="4"
                                   name="item_discount_amount"
                                   class="input-sm form-control amount"
                                   data-bs-toggle = "tooltip"
                                   title="quote_item->discount_amount"
                                   value="
<?= $numberHelper->format_amount($item->getDiscount_amount()); ?>"
                                   data-bs-toggle = "tooltip"
                                   data-placement="bottom"
                                   title="
<?= $s->getSetting('currency_symbol') . ' '
    . $translator->translate('per.item'); ?>">
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text">
                                <b><?= $vat === '0' ?
    $translator->translate('tax.rate') :
    $translator->translate('vat.rate') ?>
                                </b>
                            </span>
                            <select disabled name="item_tax_rate_id" 
                                    class="form-control" 
                                    data-bs-toggle = "tooltip" 
                                    title="quote_item->tax_rate_id">
                                <option value="0">
<?= $translator->translate('none'); ?>
                                </option>
<?php
/**
 * @var App\Invoice\Entity\TaxRate $taxRate
 */
 foreach ($taxRates as $taxRate) { ?>
                                <option value="
<?php echo $taxRate->getTaxRateId(); ?>"
<?php if ($item->getTax_rate_id() == $taxRate->getTaxRateId()) { ?>
                                    selected="selected"<?php } ?>>
<?php $taxRatePercent = $numberHelper->format_amount(
        $taxRate->getTaxRatePercent());
        $taxRateName = $taxRate->getTaxRateName();
if ($taxRatePercent >= 0.00 && null !== $taxRatePercent && null !== $taxRateName) {
    echo $taxRatePercent . '% - ' . $taxRateName;
} ?>
                                </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
<?php // Buttons for line item start here?>
                    <td class="td-vert-middle btn-group">                        
<?php if ($invEdit === true) { ?>
<?php if ($piR->repoCount((int) $item->getProduct_id()) > 0) { ?>
                            <span data-bs-toggle="tooltip"
                                  title="
<?= $translator->translate('productimage.gallery')
    . (($item->getProduct_id() > 0) ?
        ($item->getProduct()?->getProduct_name() ?? '') :
            ($item->getTask()?->getName() ?? '')); ?>">
                                <a class="btn btn-info fa fa-eye"
                                   data-bs-toggle="modal"
                                   href="#view-product-<?= $item->getId(); ?>"
                                   style="text-decoration:none">
                                </a>
                            </span>
                            <div id="view-product-<?= $item->getId(); ?>"
                                 class="modal modal-lg"
                                 tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button"
                                                    class="btn-close"
                                                    data-bs-dismiss="modal"
                                                aria-label="Close">
                                            </button>
                                        </div>
                                        <div class="modal-body">
                                            <form>
                                                <div class="form-group">
                                                    <input type="hidden"
                                                           name="_csrf"
                                                           value="<?= $csrf ?>">
<?php $productImages = $piR->repoProductImageProductquery(
    (int) $item->getProduct_id()); ?>
<?php
/**
 * @var App\Invoice\Entity\ProductImage $productImage
 */
foreach ($productImages as $productImage) { ?>
<?php if (!empty($productImage->getFile_name_original())) { ?> 
                                                    <a data-bs-toggle="modal"
                                                       class="col-sm-4">
                                                       <img src="
    <?= '/products/' . $productImage->getFile_name_original(); ?>"
                                                            class="img-fluid">
                                                    </a>
    <?php } ?> 
<?php } ?>
                                                </div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button"
                                                    class="btn btn-secondary"
                                                    data-bs-dismiss="modal">
                                        <?= $translator->translate('cancel'); ?>
                                            </button>
                                        </div>  
                                    </div>
                                </div>
                             </div>
<?php } ?>
<?php if ($s->getSetting('enable_peppol') == '1') { ?>
                             <a href="
<?= $urlGenerator->generate('quoteitemallowancecharge/index',
        [
            'quote_item_id' => $item->getId(),
            '_language' => $currentRoute->getArgument('_language')
        ],
        ['quote_item_id' => $item->getId()]) ?>" 
                                    class="btn btn-primary btn" 
                                    data-bs-toggle = "tooltip" 
                                    title="
<?= $translator->translate('allowance.or.charge.index'); ?>">
                                    <i class="
<?= $acqiR->repoQuoteItemCount($item->getId()) > 0 ? 'fa fa-list' :
        'fa fa-plus'; ?>"></i>
                             </a>
                             <?php } ?>  
<!-- Make sure to fill the third parameter of generate in order to use query
parameters --> 
                             <a href="
<?= $urlGenerator->generate('quote/delete_quote_item',
        [
            'id' => $item->getId(),
            '_language' => $currentRoute->getArgument('_language')]) ?>" 
                                class="btn btn-secondary btn" 
                                onclick="return confirm('
<?= $translator->translate('delete.record.warning'); ?>');">
                                ❌
                             </a>
                             <?php if ($item->getTask_id() > 0) { ?>    
                             <a href="
<?= $urlGenerator->generate('quoteitem/edit_task',
                                    [
                                        'id' => $item->getId(),
                                        '_language' =>
    $currentRoute->getArgument('_language')]) ?>"
                                class="btn btn-success btn">
                                🖉 
                             </a>
                            <?php } ?>
                            <?php if ($item->getProduct_id() > 0) { ?>    
                             <a href="
<?= $urlGenerator->generate('quoteitem/edit_product',
    [
        'id' => $item->getId(),
        '_language' => $currentRoute->getArgument('_language')
    ]) ?>" 
                                class="btn btn-success btn">
                                🖉 
                             </a>
                            <?php } ?>
                        <?php } ?>
                    </td>
                </tr>
<?php // Buttons for line item end here?>
                <tr>
                    <td></td>   
                    <td>    
                        <div class="input-group">
                            <span class="input-group-text"
                                  data-bs-toggle = "tooltip"
                                  title="quote_item->description">
                                    <b>
 <?= $translator->translate('description'); ?>
                                    </b>
                            </span>
                            <textarea disabled name="item_description"
                                      class="form-control" 
                                      rows="1">
<?= Html::encode($item->getDescription()); ?>
                            </textarea>
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                        <?php if ($item->getProduct_id() > 0) { ?>        
                            <span class="input-group-text">
                                <b>
<?= $translator->translate('product.unit');?>
                                </b>
                            </span>
                            <span class="input-group-text" 
                                  name="item_product_unit">
                        <?= $item->getProduct_unit();?>
                            </span>
                        <?php } ?>
                        <?php if ($item->getTask_id() > 0) { ?>        
                            <span class="input-group-text">
                                <b><?= $item->getTask()?->getName(); ?></b>
                            </span>
                            <span class="input-group-text"
                                  name="item_task_unit">
<?php echo !is_string($finishDate = $item->getTask()?->getFinish_date()) ?
        $finishDate?->format('Y-m-d') : '';?>
                            </span>
                        <?php } ?>    
                        </div>
                    </td>
                    <td class="td-amount">
                        <?php if ($item->getProduct_id() > 0) { ?>
                        <b>
  <?= $numberHelper->format_amount(($item->getQuantity() ?? 0.00)
                                  * ($item->getPrice() ?? 0.00)); ?>
                        </b>
                        <?php } ?>
                    </td>
                    <td class="td-amount"></td>
                    <td class="td-amount">
                        <b>
  <?= $numberHelper->format_amount(($item->getQuantity() ?? 0.00)
                                 * ($item->getPrice() ?? 0.00)
                                 * ($item->getTaxRate()?->getTaxRatePercent()
                                                                        ?? 0.00)
                                 / 100); ?>
                        </b>
                    </td> 
                    <td class="td-amount"></td>   
                </tr>
                <?php
                if ($s->getSetting('enable_peppol') == '1') {
/**
 * Used if Peppol is enabled in order to generate electronic
 * invoices
 * @var App\Invoice\Entity\QuoteItemAllowanceCharge $quoteItemAllowanceCharge
 */
                    foreach ($acqiR->repoQuoteItemquery($item->getId()) 
                        as $quoteItemAllowanceCharge) { ?>
    <?php $isCharge =
        ($quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == 1 ?
            true : false); ?>
                        <tr>
                            <td class="td-amount">
                                <b>
    <?= $quoteItemAllowanceCharge->getAllowanceCharge()?->getIdentifier() == '1'
            ? $translator->translate('allowance.or.charge.charge')
            : '(' . $translator->translate('allowance.or.charge.allowance') 
                . ')'; ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
    <?= $translator->translate('allowance.or.charge.reason.code') . ': ' .
    ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReasonCode() ?? '#'); ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
    <?= $translator->translate('allowance.or.charge.reason') . ': '
     . ($quoteItemAllowanceCharge->getAllowanceCharge()?->getReason() ?? '#'); ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
    <?= ($isCharge ? '' : '(') . $numberHelper->format_currency(
        $quoteItemAllowanceCharge->getAmount()) . ($isCharge ? '' : ')') ; ?>
                                </b>
                            </td>
                            <td class="td-amount"></td>
                            <td class="td-amount">
                                <b>
    <?= ($isCharge ? '' : '(') . $numberHelper->format_currency(
        $quoteItemAllowanceCharge->getVatOrTax()) . ($isCharge ? '' : ')'); ?>
                                </b>
                            </td>   
                            <td class="td-amount"></td>
                        </tr>
                    <?php } ?>
                <?php } ?> 
                <tr> 
                    <td class="td-amount"></td>
                    <td class="td-amount"></td>
                    <td class="td-amount"></td>
                    <td class="td-amount td-vert-middle"
                        style="background-color: lightblue">
                        <span>
                            <b><?= $translator->translate('subtotal'); ?></b>
                        </span>
                        <br/>
                        <span name="subtotal"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="quote_item_amount->subtotal using
                QuoteItemController/edit_product->saveQuoteItemAmount">
<!-- This subtotal is worked out in 
    QuoteItemController/edit_product->saveQuoteItemAmount function -->
<?= $numberHelper->format_currency(
    $qiaR->repoQuoteItemAmountquery($item->getId())?->getSubtotal()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><b>
(<?= $vat === '0' ? $translator->translate('discount') :
        $translator->translate('early.settlement.cash.discount') ?>)</b>
                        </span>
                        <br/>
                        <span name="item_discount_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="quote_item_amount->discount">
(<?= $numberHelper->format_currency(
        $qiaR->repoQuoteItemAmountquery($item->getId())?->getDiscount()); ?>)
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle"
                        style="background-color: lightpink">
                        <span>
                            <b>
<?= $vat === '0' ? $translator->translate('tax') :
        $translator->translate('vat.abbreviation') ?></b>
                        </span><br/>
                        <span name="item_tax_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="quote_item_amount->tax_total">
<?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery(
        $item->getId())?->getTax_total()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle"
                        style="background-color: lightyellow">
                        <span>
                            <b><?= $translator->translate('total'); ?></b>
                        </span><br/>
                        <span name="item_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="quote_item_amount->total">
<?= $numberHelper->format_currency($qiaR->repoQuoteItemAmountquery(
        $item->getId())?->getTotal()); ?>
                        </span>
                    </td>                   
                </tr>
                </tbody>
            <?php
                 $count = $count + 1;
}
/**************************/
/* Quote items end here */
/**************************/
?> 
        </table>
    </div>
     <br>
    <?php
        /***********************/
        /*   Totals start here */
        /***********************/
?> 
    <?= Html::openTag('div', ['class' => 'row']); ?>
        <div class="col-xs-12 col-md-4"
             quote_tax_rates="<?php $quoteTaxRates; ?>">
        </div>
        <div class="col-xs-12 visible-xs visible-sm"><br></div>
        <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
            <table class="table table-bordered text-right">
                <tr><i class="fa fa-info-circle"
                       data-bs-toggle="tooltip"
                       title="<?= $s->isDebugMode(19); ?>"></i>
                </tr>
                <tr>
                    <td style="width: 40%;">
                        <b><?= $translator->translate('subtotal'); ?></b>
                    </td>
                    <td style="width: 60%;background-color: lightblue"
                        class="amount"
                        id="amount_subtotal"
                        data-bs-toggle = "tooltip"
                        title="quote_amount->item_subtotal =  
                        quote_item(s)->subtotal - quote_item(s)->discount +
                        quote_item(s)->charge - quote_item(s)->allowance">
<?php echo $numberHelper->format_currency(
    $quoteAmount->getItem_subtotal() > 0.00 ? $quoteAmount->getItem_subtotal() :
    0.00); ?>       </td>
                </tr>
                <tr>
                    <td>
                        <span>
                            <b>
<?= $vat == '1' ? $translator->translate('vat.break.down') :
        $translator->translate('item.tax'); ?>
                            </b>
                        </span>    
                    </td>
                    <td class="amount"
                        style="background-color: lightpink"
                        data-bs-toggle = "tooltip"
                        id="amount_item_tax_total"
                        title="quote_amount->item_tax_total">
<?php echo $numberHelper->format_currency(
    $quoteAmount->getItem_tax_total() > 0 ? $quoteAmount->getItem_tax_total() :
    0.00); ?>       </td>
                </tr>
                <tr>
                    <td>
                        <b>
<?= $translator->translate('allowance.or.charge.shipping.handling.packaging'); ?>
                        </b>
                    </td>
                    <td class="amount"
                        id="amount_quote_allowance_charge_total"
                        data-bs-toggle = "tooltip"
                        title="quote_amount->packhandleship_total">
                        <b><?php echo $numberHelper->format_currency(
                            $packHandleShipTotal['totalAmount'] ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>
<?= A::tag()->content($vat == '1' ? $translator->translate(
                    'allowance.or.charge.shipping.handling.packaging.vat') :
                        $translator->translate(
                        'allowance.or.charge.shipping.handling.packaging.tax')
                            )->href(
    $urlGenerator->generate('quoteallowancecharge/index', [], [
        'filterQuoteNumber' => $quote->getNumber()])); ?>
                        </b>
                    </td>
                    <td class="amount"
                        id="amount_quote_allowance_charge_tax"
                        data-bs-toggle = "tooltip"
                        title="quote_amount->packhandleship_tax">
                        <b><?php echo $numberHelper->format_currency(
                            $packHandleShipTotal['totalTax'] ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
                <?php if ($vat === '0') { ?>
                <tr>
                    <td>
                        <b>
                        <?php if ($invEdit === true) { ?>
                            <a href="#add-quote-tax"
                               data-bs-toggle="modal"
                               class="btn-xs"
                               style="text-decoration:none">➕
                            </a>
                        <?php } ?>
                        <?= $translator->translate('tax'); ?>
                        </b> 
                    </td>
                    <td>
<?php if ($quoteTaxRates) {
    /**
     * @var App\Invoice\Entity\QuoteTaxRate $quoteTaxRate
     */
    foreach ($quoteTaxRates as $quoteTaxRate) { ?>
                            <div data-bs-toggle="tooltip"
                                 title="
<?= $quoteTaxRate->getInclude_item_tax() == '1' ? $included : $excluded; ?>">
                                    <input type="hidden"
                                           name="_csrf"
                                           value="<?= $csrf ?>">
                                    <?php if ($invEdit === true) { ?>
                                    
                                    

                                    <span>
<?= A::tag()
    ->addAttributes([
        'class' => 'btn btn-secondary',
        'style' => 'text-decoration:none',
        'data-bs-toggle' => 'tooltip',
        'title' => $translator->translate('delete'),
     ])
    ->content('❌')
    ->href($urlGenerator->generate('quote/delete_quote_tax_rate', [
             '_language' => $currentRoute->getArgument('_language'),
             'id'        => $quoteTaxRate->getId()
         ])
    );
?>
                                    </span>
                                    <?php } ?>
                                    <span class="text-muted">
<?php
    $taxRatePercent = $quoteTaxRate->getTaxRate()?->getTaxRatePercent();
    $numberPercent = $numberHelper->format_amount($taxRatePercent);
    $taxRateName = $quoteTaxRate->getTaxRate()?->getTaxRateName();
    if ($taxRatePercent >= 0.00
            && null !== $taxRateName
            && $numberPercent >= 0.00
            && null !== $numberPercent) {
        Html::encode($taxRateName . ' ' . $numberPercent);
    }
?>                                  </span>
                                    <span
                                    class="amount"
                                    data-bs-toggle = "tooltip"
                                    title="quote_tax_rate->quote_tax_rate_amount">
<?php echo $numberHelper->format_currency(
    $quoteTaxRate->getQuote_tax_rate_amount()); ?>
                                    </span>
                                    <br>
                            </div>        
                            <?php }
                            } else {
                                echo $numberHelper->format_currency('0');
                            } ?>
                    </td>
                </tr>
                <?php } ?>
                <?php if (($quote->getDiscount_amount() ?? 0.00) != 0.00) { ?>
                <tr>
                    <td class="td-vert-middle">
                        <b>
                            (<?= $translator->translate('discount'); ?>)
                        </b>
                    </td>
                    <td class="clearfix">
                        <div class="discount-field">
                            <div class="input-group input-group">
      <?= $numberHelper->format_currency($quote->getDiscount_amount() ?? 0.00); ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php } ?>               
                <tr>
                    <td><b><?= $translator->translate('total'); ?></b></td>
                    <td class="amount"
                        style="background-color:lightyellow"
                        id="amount_quote_total"
                        data-bs-toggle = "tooltip"
                        title="quote_amount->total">
                        <b>
        <?= $numberHelper->format_currency($quoteAmount->getTotal() ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
            </table>
        </div>
    <hr>
    