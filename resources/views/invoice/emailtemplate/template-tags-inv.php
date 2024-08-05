<?php

    declare(strict_types=1);
    
    /**
     * @var Yiisoft\Translator\TranslatorInterface $translator
     * @var array $custom_fields_inv_custom
     */
?>

        <div class="form-group">
            <label for="tags_invoice"><?= $translator->translate('i.invoices'); ?></label>
            <select id="tags_invoice" class="taginv-select form-control">
                <option value="{{{invoice_number}}}">
                    <?= $translator->translate('i.id'); ?>
                </option>
                <option value="{{{invoice_status}}}">
                    <?= $translator->translate('i.status'); ?>
                </option>
                <optgroup label="<?= $translator->translate('i.invoice_dates'); ?>">
                    <option value="{{{invoice_date_due}}}">
                        <?= $translator->translate('i.due_date'); ?>
                    </option>
                    <option value="{{{invoice_date_created}}}">
                        <?= $translator->translate('i.invoice_date'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.invoice_amounts'); ?>">
                    <option value="{{{invoice_item_subtotal}}}">
                        <?= $translator->translate('i.subtotal'); ?>
                    </option>
                    <option value="{{{invoice_item_tax_total}}}">
                        <?= $translator->translate('i.invoice_tax'); ?>
                    </option>
                    <option value="{{{invoice_total}}}">
                        <?= $translator->translate('i.total'); ?>
                    </option>
                    <option value="{{{invoice_paid}}}">
                        <?= $translator->translate('i.total_paid'); ?>
                    </option>
                    <option value="{{{invoice_balance}}}">
                        <?= $translator->translate('i.balance'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.extra_information'); ?>">
                    <option value="{{{invoice_terms}}}">
                        <?= $translator->translate('i.invoice_terms'); ?>
                    </option>
                <option value="{{{invoice_guest_url}}}">
                        <?= $translator->translate('i.guest_url'); ?>
                </option>
                        <?= $translator->translate('i.payment_method'); ?>
                </optgroup>
                <optgroup label="<?= $translator->translate('i.custom_fields'); ?>">
                    <?php
                       /**
                        * @var App\Invoice\Entity\CustomField $custom
                        */
                       foreach ($custom_fields_inv_custom as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= ($custom->getLabel() ?? '#') . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
