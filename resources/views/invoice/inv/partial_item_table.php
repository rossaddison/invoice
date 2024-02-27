<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/***
 * @var bool $show_buttons 
 */

$t_charge = $translator->translate('invoice.invoice.allowance.or.charge.charge'); 
$t_allowance = $translator->translate('invoice.invoice.allowance.or.charge.allowance');
$vat = $s->get_setting('enable_vat_registration');
?>

<div class="table-striped table-responsive">
        <table id="item_table" class="items table-primary table table-bordered no-margin">
            <thead style="display: none">
            <tr><i class="fa fa-info-circle" data-bs-toggle="tooltip" title="<?= $s->isDebugMode(7); ?>"></i></tr>    
            <tr>
                <th></th>
                <th><?= $translator->translate('i.item'); ?></th>
                <th><?= $translator->translate('i.description'); ?></th>
                <th><?= $translator->translate('invoice.invoice.note'); ?></th>
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
                    <input type="hidden" name="inv_id" maxlength="7" size="7" value="<?php echo $inv->getId(); ?>">
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
                               title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>" value="0.00">
                    </div>
                </td>
                <td td-vert-middle>
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.tax_rate'); ?></span>
                        <select name="item_tax_rate_id" class="form-control">
                            <option value="0"><?= $translator->translate('i.none'); ?></option>
                            <?php foreach ($tax_rates as $tax_rate) { ?>
                                <option value="<?php echo $tax_rate->getTax_rate_id(); ?>">
                                    <?php echo $numberhelper->format_amount($tax_rate->getTax_rate_percent()) . '% - ' . $tax_rate->getTax_rate_name(); ?>
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
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('invoice.invoice.note'); ?></span>
                        <textarea name="item_note" class="form-control"></textarea>
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.product_unit'); ?></span>
                            <select name="item_product_unit_id" class="form-control" disabled>
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($units as $unit) { ?>
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
                foreach ($inv_items as $item) { ?>
                <tbody class="item">
                <tr>
                    <td rowspan="2" class="td-icon" style="text-align: center; vertical-align: middle;">
                        <i class="fa fa-arrows"></i>
                        <h5><bold><?= " ".$count; ?></bold></h5>                       
                    </td>
                    <td class="td-text">
                        <div class="input-group">
                            <input type="text" disabled="true" maxlength="1" size="1" name="inv_id" value="<?= $item->getInv_id(); ?>" data-bs-toggle = "tooltip" title="inv_item->inv_id">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_id" value="<?= $item->getId(); ?>" data-bs-toggle = "tooltip" title="inv_item->getId()">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_product_id" value="<?= $item->getProduct_id() ? $item->getProduct_id() : '0'; ?>" data-bs-toggle = "tooltip" title="inv_item->product_id">
                            <input type="text" disabled="true" maxlength="1" size="1" name="item_task_id" value="<?= $item->getTask_id() ? $item->getTask_id() : '0' ?>" data-bs-toggle = "tooltip" title="inv_item->task_id">
                        </div>    
                        <div class="input-group">
                            <span class="input-group-text"><?= $item->getProduct_id() ? $translator->translate('i.item') : $translator->translate('i.tasks') ; ?></span>
                            <select name="item_name" class="form-control" disabled>
                            <?php if  ($item->getProduct_id()) { ?>    
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($products as $product) { ?>
                                    <option value="<?php echo $product->getProduct_id(); ?>"
                                            <?php if ($item->getProduct_id() == $product->getProduct_id()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $product->getProduct_name(); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>
                            <?php if  ($item->getTask_id()) { ?>    
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($tasks as $task) { ?>
                                    <option value="<?php echo $task->getId(); ?>"
                                            <?php if ($item->getTask_id() == $task->getId()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $task->getName(); ?>
                                    </option>
                                <?php } ?>
                            <?php } ?>        
                            </select>
                        </div>
                    </td>
                    <td class="td-amount td-quantity">
                        <div class="input-group">
                            <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                            <input disabled type="text" name="item_quantity" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="inv_item->quantity"
                                   value="<?= $numberhelper->format_amount($item->getQuantity()); ?>">
                        </div>
                    </td>
                    <td class="td-amount">
                      <div class="input-group">
                          <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                          <input disabled type="text" name="item_price" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="inv_item->price"
                                 value="<?= $numberhelper->format_amount($item->getPrice()); ?>">
                      </div>
                    </td>
                    <td class="td-amount ">
                        <div class="input-group">
                            <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.item_discount') : $translator->translate('invoice.invoice.cash.discount'); ?></span>
                            <input disabled type="text" name="item_discount_amount" class="input-sm form-control amount" data-bs-toggle = "tooltip" title="inv_item->discount_amount"
                                   value="<?= $numberhelper->format_amount($item->getDiscount_amount()); ?>"
                                   data-bs-toggle = "tooltip" data-placement="bottom"
                                   title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>">
                        </div>
                    </td>
                    
                    <?php
                       //get the percentage
                       $percentage = '';
                       foreach ($tax_rates as $tax_rate) {
                       if ($item->getTax_rate_id() == $tax_rate->getTax_rate_id()){
                          $percentage = $numberhelper->format_amount($tax_rate->getTax_rate_percent()) . '% - ' . Html::encode($tax_rate->getTax_rate_name());
                       } 
                    }?>
                    <td>
                        <div class="input-group">
                            <span class="input-group-text"><?= $vat === '0' ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></span>
                            <select disabled name="item_tax_rate_id" class="form-control" data-bs-toggle = "tooltip" title="inv_item->tax_rate_id">
                                <option value="0"><?= $translator->translate('i.none'); ?></option>
                                <?php foreach ($tax_rates as $tax_rate) { ?>
                                    <option value="<?php echo $tax_rate->getTax_rate_id(); ?>"
                                            <?php if ($item->getTax_rate_id() == $tax_rate->getTax_rate_id()) { ?>selected="selected"<?php } ?>>
                                        <?php echo $numberhelper->format_amount($tax_rate->getTax_rate_percent()) . '% - ' . Html::encode($tax_rate->getTax_rate_name()); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </td>
<?php // Buttons for line item start here ?>
                    <td class="td-icon text-right td-vert-middle">                        
                        <?php if ($show_buttons === true && $user_can_edit === true && $draft === true) { ?>
                        <span data-bs-toggle="tooltip" title="<?= $translator->translate('invoice.productimage.gallery'). (null!==$item->getProduct_id() ? $item->getProduct()?->getProduct_name() : $item->getTask()?->getName()); ?>">
                            <a class="btn btn-info fa fa-eye" data-bs-toggle="modal" href="#view-product-<?= $item->getId(); ?>" style="text-decoration:none"></a></span> 
                            <div id="view-product-<?= $item->getId(); ?>" class="modal modal-lg" tabindex="-1" role="dialog" aria-labelledby="modal_view_product_<?= $item->getId(); ?>" aria-hidden="true">
                                <div class="modal-content">
                                    <div class="modal-header">
                                      <button type="button" class="close" data-dismiss="modal"><i class="fa fa-times-circle"></i></button>
                                    </div>    
                                    <div>
                                      <?php $product_images = $piR->repoProductImageProductquery((int)$item->getProduct_id()); ?>
                                      <?php foreach ($product_images as $product_image) { ?>
                                       <?php if (!empty($product_image->getFile_name_original())) { ?> 
                                          <a data-toggle="modal" class="col-sm-4">
                                             <img src="<?= '/products/'. $product_image->getFile_name_original(); ?>"  class="img-fluid">
                                          </a>
                                       <?php } ?> 
                                      <?php } ?>
                                    </div>
                                    <div class="modal-footer">
                                    </div>  
                                </div> 
                             </div>
                             <!-- Make sure to fill the third parameter of generate in order to use query parameters --> 
                             <a href="<?= $urlGenerator->generate('acii/index', ['inv_item_id'=> $item->getId(), '_language'=>$currentRoute->getArgument('_language')], ['inv_item_id'=> $item->getId()]) ?>" class="btn btn-primary btn" data-bs-toggle = "tooltip" title="<?= $translator->translate('invoice.invoice.allowance.or.charge.index'); ?>"><i class="<?= $aciiR->repoInvItemCount((string)$item->getId()) > 0 ? 'fa fa-list' : 'fa fa-plus'; ?>"></i></a>
                             <a href="<?= $urlGenerator->generate('inv/delete_inv_item',['id'=>$item->getId(),'_language'=>$currentRoute->getArgument('_language')]) ?>" class="btn btn-danger btn" onclick="return confirm('<?= $translator->translate('i.delete_record_warning'); ?>');"><i class="fa fa-trash"></i></a>
                             <?php if  ($item->getTask_id()) { ?>    
                              <a href="<?= $urlGenerator->generate('invitem/edit_task',['id'=>$item->getId(), '_language'=>$currentRoute->getArgument('_language')]) ?>" class="btn btn-success btn"><i class="fa fa-pencil"></i></a>
                            <?php } ?>
                            <?php if  ($item->getProduct_id()) { ?>    
                              <a href="<?= $urlGenerator->generate('invitem/edit_product',['id'=>$item->getId(), '_language'=>$currentRoute->getArgument('_language')]) ?>" class="btn btn-success btn"><i class="fa fa-pencil"></i></a>
                            <?php } ?>
                        <?php } ?>
                    </td>
                </tr>
<?php // Buttons for line item end here ?>
                <tr>
                    <td class="td-textarea">
                        <div class="input-group">
                            <span class="input-group-text" data-bs-toggle = "tooltip" title="inv_item->description"><?= $translator->translate('i.description'); ?></span>
                            <textarea disabled name="item_description" class="form-control" ><?= Html::encode($item->getDescription()); ?></textarea>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text" data-bs-toggle = "tooltip" title="inv_item->note"><?= $translator->translate('invoice.invoice.note'); ?></span>
                            <textarea disabled name="item_note" class="form-control" ><?= Html::encode($item->getNote()); ?></textarea>
                        </div>
                    </td>
                    <td class="td-amount">
                        <div class="input-group">
                        <?php if  ($item->getProduct_id()) { ?>        
                            <span class="input-group-text"><?= $translator->translate('i.product_unit');?></span>
                            <span class="input-group-text" name="item_product_unit"><?= $item->getProduct_unit();?></span>
                        <?php } ?>
                        <?php if  ($item->getTask_id()) { ?>        
                            <span class="input-group-text"><?= $item->getTask()->getName(); ?></span>
                            <span class="input-group-text" name="item_task_unit"><?= $datehelper->date_from_mysql($item->getTask()->getFinish_date());?></span>
                        <?php } ?>    
                        </div>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.subtotal'); ?></span><br/>
                        
                        <span name="subtotal" class="amount" data-bs-toggle = "tooltip" title="inv_item_amount->subtotal using InvItemController/edit_product->saveInvItemAmount">
                            <!-- This subtotal is worked out in InvItemController/edit_product->saveInvItemAmount function -->
                            <?= $numberhelper->format_currency($inv_item_amount->repoInvItemAmountquery((string)$item->getId())?->getSubtotal()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $vat === '0' ? $translator->translate('i.discount') : $translator->translate('invoice.invoice.early.settlement.cash.discount') ?></span><br/>
                        <span name="item_discount_total" class="amount" data-bs-toggle = "tooltip" title="inv_item_amount->discount">
                            <?= $numberhelper->format_currency($inv_item_amount->repoInvItemAmountquery((string)$item->getId())?->getDiscount()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $vat === '0' ? $translator->translate('i.tax') : $translator->translate('invoice.invoice.vat.abbreviation') ?></span><br/>
                        <span name="item_tax_total" class="amount" data-bs-toggle = "tooltip" title="inv_item_amount->tax_total">
                            <?= $numberhelper->format_currency($inv_item_amount->repoInvItemAmountquery((string)$item->getId())?->getTax_total()); ?>
                        </span>
                    </td>
                    <td class="td-amount td-vert-middle">
                        <span><?= $translator->translate('i.total'); ?></span><br/>
                        <span name="item_total" class="amount" data-bs-toggle = "tooltip" title="inv_item_amount->total">
                            <?= $numberhelper->format_currency($inv_item_amount->repoInvItemAmountquery((string)$item->getId())?->getTotal()); ?>
                        </span>
                    </td>                   
                </tr>
                </tbody>
            <?php 
                 $count = $count + 1;} 
                 /**************************/
                 /* Invoice items end here */
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
        <div class="col-xs-12 col-md-4" inv_tax_rates="<?php $inv_tax_rates; ?>"></div>
        <div class="col-xs-12 visible-xs visible-sm"><br></div>
        <div class="col-xs-12 col-md-6 col-md-offset-2 col-lg-4 col-lg-offset-4">
            <table class="table table-bordered text-right">
                <tr><i class="fa fa-info-circle" data-bs-toggle="tooltip" title="<?= $s->isDebugMode(7); ?>"></i></tr>
                <tr>
                    <td style="width: 40%;"><?= $translator->translate('i.subtotal'); ?></td>
                    <td style="width: 60%;" class="amount" id="amount_subtotal" data-bs-toggle = "tooltip" title="inv_amount->item_subtotal =  inv_item(s)->subtotal - inv_item(s)->discount + inv_item(s)->charge"><?php echo $numberhelper->format_currency($inv_amount->getItem_subtotal() ?? 0.00); ?></td>
                </tr>                
                <?php if ($vat === '0') { ?>
                <tr>
                    <td>
                        <?php if ($show_buttons === true && $user_can_edit === true) { ?>
                            <a href="#add-inv-tax" data-toggle="modal" class="btn-xs"> <i class="fa fa-plus-circle"></i></a>
                        <?php } ?>
                        <?= $translator->translate('i.invoice_tax'); ?>
                    </td>
                    <td>
                        <?php if ($inv_tax_rates) {
                            foreach ($inv_tax_rates as $inv_tax_rate) { ?>
                                    <input type="hidden" name="_csrf" value="<?= $csrf ?>">
                                    <?php if ($show_buttons === true && $user_can_edit === true) { ?>
                                    <span  class="btn btn-xs btn-link" onclick="return confirm('<?= $translator->translate('i.delete_tax_warning'); ?>');">
                                        <a href="<?= $urlGenerator->generate('inv/delete_inv_tax_rate',['_language'=>$currentRoute->getArgument('_language'), 'id'=>$inv_tax_rate->getId()]) ?>"><i class="fa fa-trash"></i></a>
                                    </span>
                                    <?php } ?>
                                    <span class="text-muted">
                                        <?= Html::encode($inv_tax_rate->getTaxRate()->getTax_rate_name()) . ' ' . $numberhelper->format_amount($inv_tax_rate->getTaxRate()->getTax_rate_percent()) . '%' ?>
                                    </span>
                                    <span class="amount" data-bs-toggle = "tooltip" title="inv_tax_rate->inv_tax_rate_amount">
                                        <?php echo $numberhelper->format_currency($inv_tax_rate->getInv_tax_rate_amount()); ?>
                                    </span>
                                    <br>
                            <?php }
                        } else {
                            echo $numberhelper->format_currency('0');
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
                                <input id="inv_discount_amount" name="inv_discount_amount"
                                       class="discount-option form-control input-sm amount" data-bs-toggle = "tooltip" title="inv->discount_amount" disabled
                                       value="<?= $numberhelper->format_amount($inv->getDiscount_amount() != 0 ? $inv->getDiscount_amount() : ''); ?>">
                                <div
                                    class="input-group-text"><?= $s->get_setting('currency_symbol'); ?></div>
                            </div>
                        </div>
                        <div class="discount-field">
                            <div class="input-group input-group-sm">
                                <input id="inv_discount_percent" name="inv_discount_percent" data-bs-toggle = "tooltip" title="inv->discount_percent" disabled
                                       value="<?= $numberhelper->format_amount($inv->getDiscount_percent() != 0 ? $inv->getDiscount_percent() : ''); ?>"
                                       class="discount-option form-control input-sm amount">
                                <div class="input-group-text">&percnt;</div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php } ?>
<?php //------ Document Level Invoice Allowance or Charges ---// ?>               
                <?php if ($vat === '1') { ?> 
                  <?php foreach ($dl_acis as $aci) { ?>
                  <tr>
                    <td class="td-vert-middle"><?php echo ($aci->getAllowanceCharge()->getIdentifier() ? $translator->translate('invoice.invoice.allowance.or.charge.charge')  : $translator->translate('invoice.invoice.allowance.or.charge.allowance')). ': '. $aci->getAllowanceCharge()->getReason(); ?>
                        <a href="<?= $urlGenerator->generate('invallowancecharge/edit',['id'=>$aci->getId()]); ?>"><i class="fa fa-pencil"></i></a>
                        <a href="<?= $urlGenerator->generate('invallowancecharge/delete',['id'=>$aci->getId()]); ?>"><i class="fa fa-trash"></i></a></td>
                    <td class="amount"><?= ($aci->getAllowanceCharge()->getIdentifier() === false ? '(' : '').$numberhelper->format_currency($aci->getAmount() !== 0 ? $aci->getAmount() : '').($aci->getAllowanceCharge()->getIdentifier() === false ? ')' : ''); ?></td>    
                  </tr>
                  <tr>
                    <td class="td-vert-middle"><?php echo ($aci->getAllowanceCharge()->getIdentifier() ? $translator->translate('invoice.invoice.allowance.or.charge.charge.vat')  : $translator->translate('invoice.invoice.allowance.or.charge.allowance.vat')). ': '. $aci->getAllowanceCharge()->getReason(); ?></td>
                    <td class="amount"><?= ($aci->getAllowanceCharge()->getIdentifier() === false ? '(' : '').$numberhelper->format_currency($aci->getVat() !== 0 ? $aci->getVat() : '').($aci->getAllowanceCharge()->getIdentifier() === false ? ')' : ''); ?></td>    
                  </tr> 
                  <?php } ?>
                <?php } ?>
                <tr>
                    <td>
                    <span><?= $vat === '1' ? $translator->translate('invoice.invoice.vat.break.down') : $translator->translate('i.item_tax'); ?>
                    </span>    
                    </td>
                    <td class="amount" data-bs-toggle = "tooltip" id="amount_item_tax_total" title="inv_amount->item_tax_total"><?php echo $numberhelper->format_currency($inv_amount->getItem_tax_total()  ?? 0.00); ?></td>
                </tr>  
                <tr>
                    <td><b><?= $translator->translate('i.total'); ?></b></td>
                    <td class="amount" id="amount_inv_total" data-bs-toggle = "tooltip" title="inv_amount->total"><b><?php echo $numberhelper->format_currency($inv_amount->getTotal() ?? 0.00); ?></b></td>
                </tr>
            </table>
        </div>
    </div>
    <hr>