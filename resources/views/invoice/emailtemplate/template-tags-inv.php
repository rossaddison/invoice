<?php
    declare(strict_types=1);
?>

        <div class="form-group">
            <label for="tags_invoice"><?= $s->trans('invoices'); ?></label>
            <select id="tags_invoice" class="taginv-select form-control">
                <option value="{{{invoice_number}}}">
                    <?= $s->trans('id'); ?>
                </option>
                <option value="{{{invoice_status}}}">
                    <?= $s->trans('status'); ?>
                </option>
                <optgroup label="<?= $s->trans('invoice_dates'); ?>">
                    <option value="{{{invoice_date_due}}}">
                        <?= $s->trans('due_date'); ?>
                    </option>
                    <option value="{{{invoice_date_created}}}">
                        <?= $s->trans('invoice_date'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('invoice_amounts'); ?>">
                    <option value="{{{invoice_item_subtotal}}}">
                        <?= $s->trans('subtotal'); ?>
                    </option>
                    <option value="{{{invoice_item_tax_total}}}">
                        <?= $s->trans('invoice_tax'); ?>
                    </option>
                    <option value="{{{invoice_total}}}">
                        <?= $s->trans('total'); ?>
                    </option>
                    <option value="{{{invoice_paid}}}">
                        <?= $s->trans('total_paid'); ?>
                    </option>
                    <option value="{{{invoice_balance}}}">
                        <?= $s->trans('balance'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('extra_information'); ?>">
                    <option value="{{{invoice_terms}}}">
                        <?= $s->trans('invoice_terms'); ?>
                    </option>
                <option value="{{{invoice_guest_url}}}">
                        <?= $s->trans('guest_url'); ?>
                </option>
                        <?= $s->trans('payment_method'); ?>
                </optgroup>
                <optgroup label="<?= $s->trans('custom_fields'); ?>">
                    <?php foreach ($custom_fields_inv_custom as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= $custom->getLabel() . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
