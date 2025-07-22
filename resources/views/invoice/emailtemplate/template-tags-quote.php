<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $custom_fields_quote_custom
 */
?>
        <div class="form-group">
            <label for="tags_quote"><?= $translator->translate('quotes'); ?></label>
            <select id="tags_quote" class="taginv-select form-control">
                <option value="{{{quote_number}}}">
                    <?= $translator->translate('id'); ?>
                </option>
                <optgroup label="<?= $translator->translate('quote.dates'); ?>">
                    <option value="{{{quote_date_created}}}">
                        <?= $translator->translate('quote.date'); ?>
                    </option>
                    <option value="{{{quote_date_expires}}}">
                        <?= $translator->translate('expires'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?= $translator->translate('quote.amounts'); ?>">
                    <option value="{{{quote_item_subtotal}}}">
                        <?= $translator->translate('subtotal'); ?>
                    </option>
                    <option value="{{{quote_tax_total}}}">
                        <?= $translator->translate('quote.tax'); ?>
                    </option>
                    <option value="{{{quote_item_discount}}}">
                        <?= $translator->translate('discount'); ?>
                    </option>
                    <option value="{{{quote_total}}}">
                        <?= $translator->translate('total'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $translator->translate('extra.information'); ?>">
                    <option value="{{{quote_guest_url}}}">
                        <?= $translator->translate('guest.url'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?= $translator->translate('custom.fields'); ?>">
                    <?php
                        /**
                        * @var App\Invoice\Entity\CustomField $custom
                        */
                        foreach ($custom_fields_quote_custom as $custom) { ?>
                        <option value="{{{<?= 'cf_' . $custom->getId(); ?>}}}">
                            <?= ($custom->getLabel() ?? '') . ' (ID ' . $custom->getId() . ')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        