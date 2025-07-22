<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $custom_fields_inv_custom
 */
?>

        <div class="form-group">
            <label for="tags_invoice"><?php echo $translator->translate('invoices'); ?></label>
            <select id="tags_invoice" class="taginv-select form-control">
                <option value="{{{invoice_number}}}">
                    <?php echo $translator->translate('id'); ?>
                </option>
                <option value="{{{invoice_status}}}">
                    <?php echo $translator->translate('status'); ?>
                </option>
                <optgroup label="<?php echo $translator->translate('dates'); ?>">
                    <option value="{{{invoice_date_due}}}">
                        <?php echo $translator->translate('due.date'); ?>
                    </option>
                    <option value="{{{invoice_date_created}}}">
                        <?php echo $translator->translate('date'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('amounts'); ?>">
                    <option value="{{{invoice_item_subtotal}}}">
                        <?php echo $translator->translate('subtotal'); ?>
                    </option>
                    <option value="{{{invoice_item_tax_total}}}">
                        <?php echo $translator->translate('tax'); ?>
                    </option>
                    <option value="{{{invoice_total}}}">
                        <?php echo $translator->translate('total'); ?>
                    </option>
                    <option value="{{{invoice_paid}}}">
                        <?php echo $translator->translate('total.paid'); ?>
                    </option>
                    <option value="{{{invoice_balance}}}">
                        <?php echo $translator->translate('balance'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('extra.information'); ?>">
                    <option value="{{{invoice_terms}}}">
                        <?php echo $translator->translate('terms'); ?>
                    </option>
                <option value="{{{invoice_guest_url}}}">
                        <?php echo $translator->translate('guest.url'); ?>
                </option>
                        <?php echo $translator->translate('payment.method'); ?>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('custom.fields'); ?>">
                    <?php
                       /**
                         * @var App\Invoice\Entity\CustomField $custom
                        */
                       foreach ($custom_fields_inv_custom as $custom) { ?>
                        <option value="{{{<?php echo 'cf_'.$custom->getId(); ?>}}}">
                            <?php echo ($custom->getLabel() ?? '#').' (ID '.$custom->getId().')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
