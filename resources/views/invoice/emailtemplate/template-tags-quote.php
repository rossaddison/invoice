<?php
    declare(strict_types=1);
?>
        <div class="form-group">
            <label for="tags_quote"><?= $s->trans('quotes'); ?></label>
            <select id="tags_quote" class="taginv-select form-control">
                <option value="{{{quote_number}}}">
                    <?= $s->trans('id'); ?>
                </option>
                <optgroup label="<?= $s->trans('quote_dates'); ?>">
                    <option value="{{{quote_date_created}}}">
                        <?= $s->trans('quote_date'); ?>
                    </option>
                    <option value="{{{quote_date_expires}}}">
                        <?= $s->trans('expires'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $s->trans('quote_amounts'); ?>">
                    <option value="{{{quote_item_subtotal}}}">
                        <?= $s->trans('subtotal'); ?>
                    </option>
                    <option value="{{{quote_tax_total}}}">
                        <?= $s->trans('quote_tax'); ?>
                    </option>
                    <option value="{{{quote_item_discount}}}">
                        <?= $s->trans('discount'); ?>
                    </option>
                    <option value="{{{quote_total}}}">
                        <?= $s->trans('total'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $s->trans('extra_information'); ?>">
                    <option value="{{{quote_guest_url}}}">
                        <?= $s->trans('guest_url'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $s->trans('custom_fields'); ?>">
                    <?php foreach ($custom_fields_quote_custom as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= $custom->getLabel() . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        