<?php
declare(strict_types=1); 

use Yiisoft\Html\Html;
use Yiisoft\Yii\Bootstrap5\Alert;

/**
 * @var \Yiisoft\View\View $this
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 * @var string $csrf
 * @var string $action
 * @var string $title
 */

if (!empty($errors)) {
    foreach ($errors as $field => $error) {
        echo Alert::widget()->options(['class' => 'alert-danger'])->body(Html::encode($field . ':' . $error));
    }
}

$vat = $s->get_setting('enable_vat_registration') === '1' ? true : false;

?>
<div class="panel panel-default">
<div class="panel-heading">
        <i tooltip="data-toggle" title="<?= $s->isDebugMode(3)?>"><?= $translator->translate('invoice.task'); ?></i>
</div>
<form id="InvItemFormTask" method="POST" action="<?= $urlGenerator->generate(...$action)?>" enctype="multipart/form-data">
<input type="hidden" name="_csrf" value="<?= $csrf ?>">
<div class="table-striped table-responsive">
<table id="item_table" class="items table-primary table table-bordered no-margin">
<thead style="display: none">
<tr>
    <th></th>
    <th><?= $translator->translate('i.item'); ?></th>
    <th><?= $translator->translate('i.description'); ?></th>
    <th><?= $translator->translate('invoice.invoice.note'); ?></th>
    <th><?= $translator->translate('i.quantity'); ?></th>
    <th><?= $translator->translate('i.price'); ?></th>
    <th><?= $vat === false ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></th>
    <th><?= $translator->translate('i.subtotal'); ?></th>
    <th><?= $translator->translate('i.tax'); ?></th>
    <th><?= $translator->translate('i.total'); ?></th>
    <th></th>
</tr>
</thead>            
<tbody id="new_inv_item_row">
            <tr>
                <td rowspan="2" class="td-icon"><i class="fa fa-arrows cursor-move"></i>
                    <?php if ($is_recurring) : ?>
                        <br/>
                        <i title="<?= $translator->translate('i.recurring') ?>"
                           class="js-item-recurrence-toggler cursor-pointer fa fa-calendar-o text-muted"></i>
                        <input type="hidden" name="is_recurring" value=""/>
                    <?php endif; ?>
                </td>
                <td class="td-text">
                    <input type="hidden" name="inv_id" maxlength="7" size="7" value="<?= Html::encode($body['inv_id'] ??  ''); ?>">
                    <input type="hidden" name="id" maxlength="7" size="7" value="<?= Html::encode($body['id'] ??  ''); ?>">
                    <input type="hidden" name="product_id" value="<?= Html::encode($body['product_id'] ??  ''); ?>">
                    <input type="hidden" name="task_id" id="task_id" value="<?= Html::encode($body['task_id'] ?? ''); ?>">
                    <input type="hidden" name="order" id="order" value="<?= Html::encode($body['order'] ?? ''); ?>">
                    <div class="input-group" id="task-no-product">
                            <span class="input-group-text"><?= $translator->translate('invoice.task'); ?></span>
                            <select name="task_id" id="task_id" class="form-control has-feedback" required>
                                 <!-- avoid using a zero option here. This forces the user to make a task selection -->
                                 <?php foreach ($tasks as $task) { ?>
                                  <option value="<?= $task->getId() ?? ''; ?>"
                                   <?php $s->check_select(Html::encode($body['task_id'] ?? ''), $task->getId()) ?>><?= $task->getName(); ?></option>
                                 <?php } ?>
                            </select>
                    </div>
                </td>
                <td class="td-amount td-quantity">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.quantity'); ?></span>
                        <input type="number" name="quantity" class="input-sm form-control amount has-feedback" required value="<?= $numberhelper->format_amount($body['quantity'] ?? ''); ?>">
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.price'); ?></span>
                        <input type="number" name="price" class="input-sm form-control amount has-feedback" required value="<?= $numberhelper->format_amount($body['price'] ?? ''); ?>">
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $vat === false ? $translator->translate('i.item_discount') : $translator->translate('invoice.invoice.cash.discount'); ?></span>
                        <input type="number" name="discount_amount" class="input-sm form-control amount has-feedback" required
                               data-bs-toggle = "tooltip" data-placement="bottom"
                               title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>" value="<?= $numberhelper->format_amount($body['discount_amount'] ?? ''); ?>">
                    </div>
                </td>
                <td class="td-amount">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('invoice.invoice.item.charge'); ?></span>
                        <input type="number" name="charge_amount" class="input-sm form-control amount has-feedback" required
                               data-bs-toggle = "tooltip" data-placement="bottom"
                               title="<?= $s->get_setting('currency_symbol') . ' ' . $translator->translate('i.per_item'); ?>" value="<?= $numberhelper->format_amount($body['charge_amount'] ?? ''); ?>">
                    </div>
                </td>
                <td td-vert-middle>
                    <div class="input-group">
                        <span class="input-group-text"><?= $vat === false ? $translator->translate('i.tax_rate') : $translator->translate('invoice.invoice.vat.rate') ?></span>
                        <select name="tax_rate_id" class="form-control has-feedback" required>
                             <!-- avoid using a zero option here -->
                            <?php foreach ($tax_rates as $tax_rate) { ?>
                                <option value="<?php echo $tax_rate->getTax_rate_id(); ?>" <?php $s->check_select(Html::encode($body['tax_rate_id'] ?? ''), $tax_rate->getTax_rate_id()) ?>>
                                    <?php echo $numberhelper->format_amount($tax_rate->getTax_rate_percent()) . '% - ' . $tax_rate->getTax_rate_name(); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </td>
                <!-- see line 896 InvController: id modal-choose-items lies on views/product/modal_product_lookups_inv.php-->
                <td class="td-icon text-right td-vert-middle">
                    <button class="btn btn btn-primary" href="#modal-choose-tasks" id="modal-choose-tasks" data-toggle="modal"><i class="bi bi-ui-checks" data-bs-toggle = "tooltip" title="<?= $translator->translate('i.add_task'); ?>"></i></button>                   
                    <button type="submit" class="btn btn btn-info" data-bs-toggle = "tooltip" title="invitem/add_task"><i class="fa fa-plus"></i><?= $translator->translate('i.save'); ?></button>
                </td>              
            </tr>
            <tr>
                <td class="td-textarea">
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('i.description'); ?></span>
                        <textarea name="description" class="form-control"><?= Html::encode($body['description'] ??  ''); ?></textarea>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><?= $translator->translate('invoice.invoice.note'); ?></span>
                        <textarea name="note" class="form-control"><?= Html::encode($body['note'] ??  ''); ?></textarea>
                    </div>
                </td>
                <td class="td-amount">                    
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
                    <span><?= $translator->translate('invoice.invoice.item.charge') ?></span><br/>
                    <span name="charge_total" class="amount"></span>
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
<div class="col-xs-12 col-md-4">           
            <div class="btn-group">
               <button hidden class="btn_inv_item_add_row btn btn-primary btn-md active"><i class="fa fa-plus"></i><?php echo $translator->translate('i.add_new_row'); ?></button>                              
            </div>           
</div>
</form>
<br>
<br>
</div>