<?php

declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;
use Yiisoft\Yii\Bootstrap5\AlertType;

/**
 * @see App\Invoice\SalesOrderItem\SalesOrderItemController
 * @var App\Invoice\Helpers\NumberHelper $numberHelper
 * @var App\Invoice\Setting\SettingRepository $s
 * @var App\Invoice\SalesOrderItem\SalesOrderItemForm $form
 * @var Yiisoft\View\View $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var array $errors
 * @var array $products
 * @var array $quotes   
 * @var array $tax_rates        
 * @var array $units
 * @var string $actionName
 * @var string $csrf
 * @var string $title
 * @psalm-var array<string, Stringable|null|scalar> $actionArguments
 */

if ($errors) {
    /**
     * @var string $error
     */
    foreach ($errors as $field => $error) {
        echo Alert::widget()
             ->type(AlertType::DANGER)
             ->body($field . ':' . $error, true)   
             ->dismissable(true)
             ->render();
    }
}
$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;
?>
<div class="panel panel-default">
<div class="panel-heading">
        <?= $translator->translate('i.item'); ?>
</div>
<form id="SalesOrderItemForm" method="POST" action="<?= $urlGenerator->generate($actionName, $actionArguments)?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div class="table-striped">
<table id="item_table" class="items table-primary table table-bordered no-margin">
<thead style="display: none">
<tr>
    <th></th>
    <th><?= $translator->translate('i.item'); ?></th>
    <th><?= $translator->translate('i.description'); ?></th>
    <th><?= $translator->translate('i.quantity'); ?></th>
    <th><?= $translator->translate('i.price'); ?></th>
    <th><?= $vat === false ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></th>
    <th><?= $translator->translate('i.subtotal'); ?></th>
    <th><?= $translator->translate('i.tax'); ?></th>
    <th><?= $translator->translate('i.total'); ?></th>
    <th></th>
</tr>
</thead>
<tbody id="new_salesorder_item_row">
        <tr>
            <td rowspan="2" class="td-icon"><i class="fa fa-arrows cursor-move"></i></td>
            <td class="td-text">
                <input type="text" disabled name="so_id" maxlength="1" size=1" value="<?= Html::encode($body['so_id'] ??  ''); ?>">
                <input type="text" disabled name="id" maxlength="1" size="1" value="<?= Html::encode($body['id'] ??  ''); ?>">
                <input type="text" disabled="true" maxlength="1" size="1" name="item_product_id" value="<?= Html::encode($body['product_id'] ??  ''); ?>" data-bs-toggle = "tooltip" title="salesorder_item->product_id">
                <input type="text" name="peppol_po_itemid" id="peppol_po_itemid" value="<?= Html::encode($body['peppol_po_itemid'] ?? ''); ?>" placeholder="Peppol PurchaseOrder Item Id (a.k.a Buyers Item Identification)" data-bs-toggle = "tooltip" title="https://docs.peppol.eu/poacc/billing/3.0/bis/#_item_identifiers">
                <input type="text" name="peppol_po_lineid" id="peppol_po_lineid" value="<?= Html::encode($body['peppol_po_lineid'] ?? ''); ?>" placeholder="Peppol PurchaseOrder Line Id" data-bs-toggle = "tooltip" title="(cac:OrderLineReference/cbc:LineID">
                <input type="hidden" disabled name="name" value="<?= Html::encode($body['name'] ??  ''); ?>">
                <input type="hidden" disabled name="order" id="order" value="<?= Html::encode($body['order'] ?? ''); ?>">
                <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.item'); ?></span>
                        <select name="product_id" id="product_id" class="form-control has-feedback" required disabled>
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                             <?php 
                             /**
                              * @var App\Invoice\Entity\Product $product
                              */
                             foreach ($products as $product) { ?>
                              <option value="<?= $product->getProduct_id() ?: ''; ?>"
                               <?php $s->check_select(Html::encode($body['product_id'] ?? ''), $product->getProduct_id()) ?>
                               ><?= $product->getProduct_name(); ?></option>
                             <?php } ?>
                        </select>
                </div>
            </td>
            <td class="td-amount td-quantity">
                <div class="input-group">
                    <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                    <input type="text" name="quantity" class="input-sm form-control amount has-feedback" required disabled value="<?= $numberHelper->format_amount($body['quantity'] ?? ''); ?>">
                </div>
            </td>
            <td class="td-amount">
                <div class="input-group">
                    <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                    <input type="number" name="price" class="input-sm form-control amount has-feedback" required disabled value="<?= $numberHelper->format_amount($body['price'] ?? ''); ?>">
                </div>
            </td>
            <td class="td-amount">
                <div class="input-group">
                     <span class="input-group-text"><?= $vat === false ? $translator->translate('i.item_discount') : $translator->translate('invoice.invoice.cash.discount'); ?></span>
                    <input type="number" name="discount_amount" class="input-sm form-control amount has-feedback" required disabled
                           data-bs-toggle = "tooltip" data-placement="bottom"
                           title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>" value="<?= $numberHelper->format_amount($body['discount_amount'] ?? ''); ?>">
                </div>
            </td>
            <td td-vert-middle>
                <div class="input-group">
                    <span class="input-group-text"><?= $vat === false ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></span>
                    <select name="tax_rate_id" class="form-control has-feedback" required disabled>
                        <option value=""> <?= $translator->translate('i.tax_rate'); ?></option>
                        <?php
                            /**
                             * @var App\Invoice\Entity\TaxRate $tax_rate
                             */    
                            foreach ($tax_rates as $tax_rate) { ?>
                            <option value="<?= $tax_rate->getTax_rate_id(); ?>" <?php $s->check_select(Html::encode($body['tax_rate_id'] ?? ''), $tax_rate->getTax_rate_id()) ?>>
                                <?php
                                    $taxRatePercent = $tax_rate->getTax_rate_percent();
                                    $taxRateName = $tax_rate->getTax_rate_name();
                                    if (null!==$taxRatePercent && null!==$taxRateName) {
                                        $formattedPercent = $numberHelper->format_amount($taxRatePercent);
                                        if (null!==$formattedPercent) {
                                            echo  $formattedPercent. '% - ' .$taxRateName;
                                        }
                                    } else {
                                        echo '%';
                                    }    
                                ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </td>
            <td class="td-icon text-right td-vert-middle">                   
                <button type="submit" class="btn btn btn-info" data-bs-toggle = "tooltip" title="salesorderitem/edit"><i class="fa fa-plus"></i><?= $translator->translate('i.save'); ?></button>
            </td>
        </tr>
        <tr>
            <td class="td-textarea">
                <div class="input-group">
                    <span class="input-group-text"><?= $translator->translate('i.description'); ?></span>
                    <textarea disabled name="description" class="form-control"><?= Html::encode($body['description'] ??  ''); ?></textarea>
                </div>
            </td>
            <td class="td-amount">
                <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.product_unit'); ?></span>
                        <select name="product_unit_id" class="form-control has-feedback" required disabled>
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                            <?php
                                /**
                                 * @var App\Invoice\Entity\Unit $unit
                                 */
                                foreach ($units as $unit) { ?>
                                <option value="<?= $unit->getUnit_id(); ?>" <?php $s->check_select(Html::encode($body['product_unit_id'] ?? ''), $unit->getUnit_id()) ?>>
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
                <span><?= $vat === false ? $translator->translate('i.discount') : $translator->translate('invoice.invoice.early.settlement.cash.discount') ?></span><br/>
                <span name="discount_total" class="amount"></span>
            </td>
            <td class="td-amount td-vert-middle">
                <span><?= $vat === false ? $translator->translate('i.tax') : $translator->translate('invoice.invoice.vat.abbreviation')  ?></span><br/>
                <span name="tax_total" class="amount"></span>
            </td>
            <td class="td-amount td-vert-middle">
                <span><?= $translator->translate('i.total'); ?></span><br/>
                <span name="total" class="amount"></span>
            </td>
        </tr>
</tbody>
</table>
</div>
</form>
<br>
<br>
</div>