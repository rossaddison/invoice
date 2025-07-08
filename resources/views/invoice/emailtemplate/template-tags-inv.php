<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields_inv_custom
 */
?>

        <div class="form-group">
            <label for="tags_invoice"><?= $translator->translate('invoices'); ?></label>
            <select id="tags_invoice" class="taginv-select form-control">
                <option value="{{{invoice_number}}}">
                    <?= $translator->translate('id'); ?>
                </option>
                <option value="{{{invoice_status}}}">
                    <?= $translator->translate('status'); ?>
                </option>
                <optgroup label="<?= $translator->translate('dates'); ?>">
                    <option value="{{{invoice_date_due}}}">
                        <?= $translator->translate('due.date'); ?>
                    </option>
                    <option value="{{{invoice_date_created}}}">
                        <?= $translator->translate('date'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('amounts'); ?>">
                    <option value="{{{invoice_item_subtotal}}}">
                        <?= $translator->translate('subtotal'); ?>
                    </option>
                    <option value="{{{invoice_item_tax_total}}}">
                        <?= $translator->translate('tax'); ?>
                    </option>
                    <option value="{{{invoice_total}}}">
                        <?= $translator->translate('total'); ?>
                    </option>
                    <option value="{{{invoice_paid}}}">
                        <?= $translator->translate('total.paid'); ?>
                    </option>
                    <option value="{{{invoice_balance}}}">
                        <?= $translator->translate('balance'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('extra.information'); ?>">
                    <option value="{{{invoice_terms}}}">
                        <?= $translator->translate('terms'); ?>
                    </option>
                <option value="{{{invoice_guest_url}}}">
                        <?= $translator->translate('guest.url'); ?>
                </option>
                        <?= $translator->translate('payment.method'); ?>
                </optgroup>
                <optgroup label="<?= $translator->translate('custom.fields'); ?>">
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
