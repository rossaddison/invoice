<?php
    declare(strict_types=1);
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.salesorders'); ?>
            </div>
            <div class="panel-body">
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label for="settings[default_client_purchase_order_group]" <?= $s->where('default_invoice_group'); ?>>
                                <?= $translator->translate('invoice.salesorder.default.group'); ?>
                            </label>
                            <?php $body['settings[default_client_purchase_order_group]'] = $s->get_setting('default_client_purchase_order_group');?>
                            <select name="settings[default_client_purchase_order_group]" id="settings[default_client_purchase_order_group]"
                                class="form-control" >
                                <option value=""><?= $s->trans('none'); ?></option>
                                <?php foreach ($invoice_groups as $invoice_group) { ?>
                                    <option value="<?= $invoice_group->getId(); ?>"
                                        <?php $s->check_select($body['settings[default_client_purchase_order_group]'], $invoice_group->getId()); ?>>
                                        <?= $invoice_group->getName(); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>