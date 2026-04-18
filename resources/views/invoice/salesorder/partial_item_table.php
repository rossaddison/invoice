<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;

/**
 * @var App\Infrastructure\Persistence\SalesOrder\SalesOrder $so
 * @var App\Infrastructure\Persistence\SalesOrderAmount\SalesOrderAmount $soAmount
 * @var App\Invoice\Helpers\DateHelper $dateHelper
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\ProductImage\ProductImageRepository $piR
 * @var App\Invoice\SalesOrderItemAmount\SalesOrderItemAmountRepository $soiaR
 * @var App\Invoice\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceChargeRepository $acsoiR
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Session\SessionInterface $session
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Router\CurrentRoute $currentRoute
 * @var array $soItems
 * @var array $soTaxRates
 * @var array $products
 * @var array $tasks
 * @var array $taxRates
 * @var array $units
 * @var bool $draft
 * @var bool $invEdit
 * @var bool $invView
 * @var bool $editClientPeppol
 * @var string $csrf
 * @var string $included
 * @var string $excluded
 */

$vat = $s->getSetting('enable_vat_registration');
$subtotalTooltip = 'sales_order_amount->item_subtotal ='
    .   'sales_order_item(s)->subtotal - sales_order_item(s)->discount'
    .   '+ sales_order_item(s)->charge"';
?>

<div>
        <table id="item_table"
               class="items table table-responsive table-bordered no-margin">
            <thead>
            <tr><i class="bi bi-info-circle"
                   data-bs-toggle="tooltip"
                   title="<?= $s->isDebugMode(20); ?>"></i></tr>
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
 * @var App\Infrastructure\Persistence\SalesOrderItem\SalesOrderItem $item
 */
foreach ($soItems as $item) {
    $productId = $item->getProductId();
    $taskId = $item->getTaskId();
    $productRef = '';
    $taskRef = '';
    if ($productId > 0) {
        $productRef =  new A()
           ->href($urlGenerator->generate('product/view',
                [
                    '_language' => (string) $session->get('_language'),
                    'id' => $productId,
                ])
           )
           ->content((string) $productId)
           ->render();
    }
    if ($taskId > 0) {
        $taskRef =  new A()
           ->href($urlGenerator->generate('task/view',
                   [
                       '_language' => (string) $session->get('_language'),
                       'id' => $taskId,
                   ])
           )
           ->content((string) $taskId)
           ->render();
    }
    ?>
                <tbody class="item">
                <tr>
                    <td class="td-text" style="background-color: lightgreen">
                        <b>
                            <div class="input-group">
<?php echo $count
        . '-'
        . (string) $item->getSalesOrderId()
        . '-'
        . (string) $item->reqId()
        . '-'
        . ((string) $productId > 0 ? $productRef : '')
        . ((string) $taskId > 0 ? $taskRef : ''); ?>

                            </div>
                            <div class="input-group">
<!--  This value is editable on our quote if the client or customer is going
      to pay by Peppol. They have to supply their corresponding Purchase Order
      Item Id here.
      https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
              cac-InvoiceLine/cac-Item/cac-BuyersItemIdentification/cbc-ID/" -->
                                <input type="text"
                                       disabled="true"
                                       placeholder="Item Id"
                                       maxlength="8"
                                       size="8"
                                       name="item_peppol_po_itemid"
                                       value="<?= $item->getPeppolPoItemid();?>"
                                       data-bs-toggle = "tooltip"
                                       title="salesorder_item->peppol_po_itemid">

<!-- This value is editable on our quote if the client or customer is going to
     pay by Peppol. They have to supply their corresponding Purchase Order Line
     Number here. https://docs.peppol.eu/poacc/billing/3.0/syntax/ubl-invoice/
     cac-InvoiceLine/cac-OrderLineReference/cbc-LineID/" -->
                                <input type="text"
                                       disabled="true"
                                       placeholder="Line Id"
                                       maxlength="8"
                                       size="8"
                                       name="item_peppol_po_lineid"
                                       value="<?= $item->getPeppolPoLineid();?>"
                                       data-bs-toggle = "tooltip"
                                       title="salesorder_item->peppol_po_lineid">
                            </div>
                        </b>
                    </td>
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text">
                                <b>
<?= $item->getProductId() > 0 ? $translator->translate('item') :
        $translator->translate('tasks') ; ?>
                                </b>
                            </span>
                            <select name="item_name"
                                    class="form-control form-control-lg"
                                    disabled>
                            <?php if ($item->getProductId() > 0) { ?>
                                <option value="0">
                                    <?= $translator->translate('none'); ?>
                                </option>
                                <?php
                                /**
                                 * @var App\Invoice\Entity\Product $product
                                 */
                                foreach ($products as $product) { ?>
                                    <option value="
                                        <?php echo $product->getProductId(); ?>"
    <?php if ($item->getProductId() == $product->getProductId()) { ?>
                                        selected="selected"<?php } ?>>
<?php echo $product->getProductName(); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                            <?php if ($item->getTaskId() > 0) { ?>
                                <option value="0">
                                <?= $translator->translate('none'); ?></option>
                                <?php
                                /**
                                 * @var App\Infrastructure\Persistence\Task\Task $task
                                 */
                                foreach ($tasks as $task) { ?>
                                    <option value="<?php echo $task->reqId(); ?>"
    <?php if ($item->getTaskId() == $task->reqId()) { ?>
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
                                <b><?= $translator->translate('quantity'); ?></b>
                            </span>
                            <input disabled
                                   type="text"
                                   maxlength="4"
                                   size="4"
                                   name="item_quantity"
                                   class="input-sm form-control amount"
                                   data-bs-toggle = "tooltip"
                                   title="sales_order_item->quantity"
                                   value="
    <?= $numberHelper->formatAmount($item->getQuantity()); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                      <div class="input-group">
                          <span class="input-group-text">
                              <b>
                                <?= $translator->translate('price'); ?>
                              </b>
                          </span>
                          <input disabled type="text"
                                 maxlength="4"
                                 size="4"
                                 name="item_price"
                                 class="input-sm form-control amount"
                                 data-bs-toggle = "tooltip"
                                 title="sales_order_item->price"
                                 value="
    <?= $numberHelper->formatAmount($item->getPrice()); ?>">
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
                                   title="sales_order_item->discount_amount"
                                   value="
    <?= $numberHelper->formatAmount($item->getDiscountAmount()); ?>"
                                   data-bs-toggle = "tooltip"
                                   data-placement="bottom"
                                   title="
    <?= $s->getSetting('currency_symbol') . ' ' .
            $translator->translate('per.item'); ?>">
                        </div>
                    </td>

                    <td>
                        <div class="input-group">
                            <span class="input-group-text">
                                <b>
    <?= $vat === '0' ? $translator->translate('tax.rate') :
                $translator->translate('vat.rate') ?></b>
                            </span>
                            <select disabled
                                    name="item_tax_rate_id"
                                    class="form-control form-control-lg"
                                    data-bs-toggle = "tooltip"
                                    title="quote_item->tax_rate_id">
                                <option value="0">
                    <?= $translator->translate('none'); ?>
                                </option>
                                <?php
                                /**
                                 * @var App\Infrastructure\Persistence\TaxRate\TaxRate $taxRate
                                 */
    foreach ($taxRates as $taxRate) { ?>
                                    <option value="
    <?php echo $taxRate->reqId(); ?>"
        <?php if ($item->getTaxRateId() == $taxRate->reqId()) { ?>
                                        selected="selected"<?php } ?>>
                <?php
                    $taxRatePercent = $numberHelper->formatAmount(
                            $taxRate->getTaxRatePercent());
                                $taxRateName = $taxRate->getTaxRateName();
                                if ($taxRatePercent >= 0.00
                                        && null !== $taxRatePercent
                                        && null !== $taxRateName) {
                                    echo $taxRatePercent . '% - ' . $taxRateName;
                                }
                                ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
<?php // Buttons for line item start here?>
                    <td class="td-vert-middle btn-group">
                        <?php if ($invEdit === true) { ?>
<?php if ($piR->repoCount((int) $item->getProductId()) > 0) { ?>
                            <span data-bs-toggle="tooltip"
                                  title="
    <?= $translator->translate('productimage.gallery') .
            (($item->getProductId() > 0) ?
                ($item->getProduct()?->getProductName() ?? '') :
                    ($item->getTask()?->getName() ?? '')); ?>">
                                <a class="btn btn-info"
                                   data-bs-toggle="modal"
                                   href="#view-product-<?= $item->reqId(); ?>"
                                   style="text-decoration:none">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </span>
                            <div id="view-product-<?= $item->reqId(); ?>"
                                 class="modal modal-lg" tabindex="-1">
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
    <?php $productImages =
        $piR->repoProductImageProductquery((int) $item->getProductId()); ?>
    <?php
    /**
     * @var App\Invoice\Entity\ProductImage $productImage
     */
    foreach ($productImages as $productImage) { ?>
        <?php if (!empty($productImage->getFileNameOriginal())) { ?>
                                                    <a data-bs-toggle="modal"
                                                       class="col-sm-4">
                                                    <img src="
                <?= '/products/' . $productImage->getFileNameOriginal(); ?>"
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
                        <?php } ?>
                        <?php if ($editClientPeppol === true) { ?>
                            <span>
                                <a class="btn btn-primary"
                                   href="
<?= $urlGenerator->generate('salesorderitem/edit', ['id' => $item->reqId()]); ?>"
                                   style="text-decoration:none"><?= '🖉'; ?></a>
                            </span>
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
                            <textarea disabled
                                      name="item_description"
                                      class="form-control form-control-lg"
                                      rows="1">
                                <?= Html::encode($item->getDescription()); ?>
                            </textarea>
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                        <?php if ($item->getProductId() > 0) { ?>
                            <span class="input-group-text">
                                <b>
                                <?= $translator->translate('product.unit');?>
                                </b>
                            </span>
                            <span class="input-group-text"
                                  name="item_product_unit">
                                    <?= $item->getProductUnit();?>
                            </span>
                        <?php } ?>
                        <?php if ($item->getTaskId() > 0) { ?>
                            <span class="input-group-text">
                                <b>
                                    <?= $item->getTask()?->getName(); ?>
                                </b>
                            </span>
                            <span class="input-group-text"
                                  name="item_task_unit">
                        <?= !is_string(
                            $finishDate = $item->getTask()?->getFinishDate()) ?
                                $finishDate?->format('Y-m-d') : '';?>
                            </span>
                        <?php } ?>
                        </div>
                    </td>
                    <td class="td-amount">
                        <?php if ($item->getProductId() > 0) { ?>
                        <b>
  <?= $numberHelper->formatAmount(($item->getQuantity() ?? 0.00)
                                  * ($item->getPrice() ?? 0.00)); ?>
                        </b>
                        <?php } ?>
                    </td>
                    <td class="td-amount"></td>
                    <td class="td-amount">
                        <b>
  <?= $numberHelper->formatAmount(($item->getQuantity() ?? 0.00)
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
 * @var App\Infrastructure\Persistence\SalesOrderItemAllowanceCharge\SalesOrderItemAllowanceCharge $acsoi
 */
foreach ($acsoiR->repoSalesOrderItemquery((string) $item->reqId()) as $acsoi) { ?>
    <?php $isCharge =
        ($acsoi->getAllowanceCharge()?->getIdentifier() == 1 ? true : false); ?>
                        <tr>
                            <td class="td-amount">
                                <b>
        <?= $acsoi->getAllowanceCharge()?->getIdentifier() == '1'
                ? $translator->translate('allowance.or.charge.charge')
                : '(' . $translator->translate('allowance.or.charge.allowance')
                    . ')'; ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
        <?= $translator->translate('allowance.or.charge.reason.code') . ': ' .
                    ($acsoi->getAllowanceCharge()?->getReasonCode() ?? '#'); ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
                <?= $translator->translate('allowance.or.charge.reason') . ': '
                    . ($acsoi->getAllowanceCharge()?->getReason() ?? '#'); ?>
                                </b>
                            </td>
                            <td class="td-amount">
                                <b>
                <?= ($isCharge ? '' : '(') . $numberHelper->formatCurrency(
                    $acsoi->getAmount()) . ($isCharge ? '' : ')') ; ?>
                                </b>
                            </td>
                            <td class="td-amount"></td>
                            <td class="td-amount">
                                <b>
                    <?= ($isCharge ? '' : '(') . $numberHelper->formatCurrency(
                        $acsoi->getVatOrTax()) . ($isCharge ? '' : ')'); ?>
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
                              title="sales_order_item_amount">
                        <?= $numberHelper->formatCurrency(
                                $soiaR->repoSalesOrderItemAmountquery((string) 
                                        $item->reqId())?->getSubtotal()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span>
                            <b>(
    <?= $vat === '0' ? $translator->translate('discount') :
            $translator->translate('early.settlement.cash.discount') ?>)
                            </b>
                        </span>
                        <br/>
                        <span name="item_discount_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="sales_order_item_amount->discount">
                        (<?= $numberHelper->formatCurrency(
                                $soiaR->repoSalesOrderItemAmountquery((string) 
                                        $item->reqId())?->getDiscount()); ?>)
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle"
                        style ="background-color: lightpink">
                        <span>
                            <b>
                        <?= $vat === '0' ? $translator->translate('tax') :
                                $translator->translate('vat.abbreviation') ?>
                            </b>
                        </span>
                        <br/>
                        <span name="item_tax_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="sales_order_item_amount->tax_total">
                            <?= $numberHelper->formatCurrency(
    $soiaR->repoSalesOrderItemAmountquery((string) $item->reqId())?->getTaxTotal()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle"
                        style="background-color: lightyellow">
                        <span>
                            <b>
                                <?= $translator->translate('total'); ?>
                            </b>
                        </span>
                        <br/>
                        <span name="item_total"
                              class="amount"
                              data-bs-toggle = "tooltip"
                              title="sales_order_item_amount->total">
                            <?= $numberHelper->formatCurrency(
    $soiaR->repoSalesOrderItemAmountquery((string) $item->reqId())?->getTotal()); ?>
                        </span>
                    </td>
                </tr>
                </tbody>
            <?php
                 $count = $count + 1;
}
/**************************/
/* Sales order items end here */
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
             sales_order_tax_rates="<?php $soTaxRates; ?>">
        </div>
        <div class="col-xs-12 visible-xs visible-sm">
        <br>
        </div>
        <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
            <table class="table table-bordered text-right">
                <tr>
                    <i class="bi bi-info-circle"
                       data-bs-toggle="tooltip"
                       title="<?= $s->isDebugMode(20); ?>">
                    </i>
                </tr>
                <tr>
                    <td style="width: 40%;">
                        <b>
             <?= $translator->translate('subtotal'); ?>
                        </b>
                    </td>
                    <td style="width: 60%;background-color: lightblue"
                        class="amount"
                        id="amount_subtotal"
                        data-bs-toggle = "tooltip"
                        title="<?= $subtotalTooltip; ?>">
                            <?php echo $numberHelper->formatCurrency(
                                    $soAmount->getItemSubtotal() > 0.00 ?
                                    $soAmount->getItemSubtotal() : 0.00); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span>
                            <b>
                                <?= $vat == '1' ?
                                    $translator->translate('vat.break.down') :
                                    $translator->translate('item.tax'); ?>
                            </b>
                        </span>
                    </td>
                    <td class="amount"
                        style="background-color: lightpink"
                        data-bs-toggle = "tooltip"
                        id="amount_item_tax_total"
                        title="sales_order_amount->item_tax_total">
                            <?php echo $numberHelper->formatCurrency(
                                    $soAmount->getItemTaxTotal() > 0 ?
                                    $soAmount->getItemTaxTotal() : 0.00); ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>
<?= $translator->translate('allowance.or.charge.shipping.handling.packaging'); ?>
                        </b>
                    </td>
                    <td class="amount"
                        id="amount_sales_order_allowance_charge_total"
                        data-bs-toggle = "tooltip"
                        title="sales_order_amount->packhandleship_total">
                        <b><?php echo $numberHelper->formatCurrency(
                            $packHandleShipTotal['totalAmount'] ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
                <tr>
                    <td>
                        <b>
<?= $vat == '1' ? $translator->translate(
                    'allowance.or.charge.shipping.handling.packaging.vat') :
                        $translator->translate(
                        'allowance.or.charge.shipping.handling.packaging.tax'); ?>
                        </b>
                    </td>
                    <td class="amount"
                        id="amount_sales_order_allowance_charge_tax"
                        data-bs-toggle = "tooltip"
                        title="sales_order_amount->packhandleship_tax">
                        <b><?php echo $numberHelper->formatCurrency(
                            $packHandleShipTotal['totalTax'] ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
                <?php if ($vat === '0') { ?>
                <tr>
                    <td>
                        <b>
                            <?= $translator->translate('tax'); ?>
                        </b>
                    </td>
                    <td>
                    <?php if ($soTaxRates) {
                        /**
                         * @var App\Invoice\Entity\SalesOrderTaxRate $soTaxRate
                         */
                        foreach ($soTaxRates as $soTaxRate) { ?>
                            <div data-bs-toggle="tooltip"
                                 title="
    <?= $soTaxRate->getIncludeItemTax() == '1' ? $included : $excluded; ?>">
                                    <input type="hidden"
                                           name="_csrf"
                                           value="<?= $csrf ?>">
                                    <span class="text-muted">
        <?php
            $taxRatePercent = $soTaxRate->getTaxRate()?->getTaxRatePercent();
            $numberPercent = $numberHelper->formatAmount($taxRatePercent);
            $taxRateName = $soTaxRate->getTaxRate()?->getTaxRateName();
            if ($taxRatePercent >= 0.00 && null !== $taxRateName
                    && $numberPercent >= 0.00 &&
                    null !== $numberPercent) {
                echo Html::encode(' '
                        . $taxRateName
                        . ' '
                        . $numberPercent
                        . ' ');
            }
        ?>
                                    </span>
                                    <span class="amount"
                                          data-bs-toggle = "tooltip"
                                          title=
                            "sales_order_tax_rate->sales_order_tax_rate_amount">
<?= $numberHelper->formatCurrency($soTaxRate->getSalesOrderTaxRateAmount()); ?>
                                    </span>
                                    <br>
                            </div>
                            <?php }
                            } else {
                                echo $numberHelper->formatCurrency('0');
                            } ?>
                    </td>
                </tr>
                <?php } ?>
                <?php if (($so->getDiscountAmount() ?? 0.00) != 0.00) { ?>
                <tr>
                    <td class="td-vert-middle">
                        <b>(<?= $translator->translate('discount'); ?>)</b>
                    </td>
                    <td class="clearfix">
                        <div class="discount-field">
                            <div class="input-group input-group">
      <?= $numberHelper->formatCurrency($so->getDiscountAmount() ?? 0.00); ?>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <td>
                        <b><?= $translator->translate('total'); ?></b>
                    </td>
                    <td class="amount"
                        style="background-color:lightyellow"
                        id="amount_sales_order_total"
                        data-bs-toggle = "tooltip"
                        title="sales_order_amount->total">
                        <b>
        <?= $numberHelper->formatCurrency($soAmount->getTotal() ?? 0.00); ?>
                        </b>
                    </td>
                </tr>
            </table>
        </div>
    <hr>
