<?php
    declare(strict_types=1);
?>
        <div class="form-group">
            <label for="tags_quote"><?= $translator->translate('i.quotes'); ?></label>
            <select id="tags_quote" class="taginv-select form-control">
                <option value="{{{quote_number}}}">
                    <?= $translator->translate('i.id'); ?>
                </option>
                <optgroup label="<?= $translator->translate('i.quote_dates'); ?>">
                    <option value="{{{quote_date_created}}}">
                        <?= $translator->translate('i.quote_date'); ?>
                    </option>
                    <option value="{{{quote_date_expires}}}">
                        <?= $translator->translate('i.expires'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.quote_amounts'); ?>">
                    <option value="{{{quote_item_subtotal}}}">
                        <?= $translator->translate('i.subtotal'); ?>
                    </option>
                    <option value="{{{quote_tax_total}}}">
                        <?= $translator->translate('i.quote_tax'); ?>
                    </option>
                    <option value="{{{quote_item_discount}}}">
                        <?= $translator->translate('i.discount'); ?>
                    </option>
                    <option value="{{{quote_total}}}">
                        <?= $translator->translate('i.total'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $translator->translate('i.extra_information'); ?>">
                    <option value="{{{quote_guest_url}}}">
                        <?= $translator->translate('i.guest_url'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $translator->translate('i.custom_fields'); ?>">
                    <?php foreach ($custom_fields_quote_custom as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= $custom->getLabel() . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        