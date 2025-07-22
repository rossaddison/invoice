<?php

declare(strict_types=1);

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $custom_fields_quote_custom
 */
?>
        <div class="form-group">
            <label for="tags_quote"><?php echo $translator->translate('quotes'); ?></label>
            <select id="tags_quote" class="taginv-select form-control">
                <option value="{{{quote_number}}}">
                    <?php echo $translator->translate('id'); ?>
                </option>
                <optgroup label="<?php echo $translator->translate('quote.dates'); ?>">
                    <option value="{{{quote_date_created}}}">
                        <?php echo $translator->translate('quote.date'); ?>
                    </option>
                    <option value="{{{quote_date_expires}}}">
                        <?php echo $translator->translate('expires'); ?>
                    </option>
                </optgroup>
                <optgroup label="<?php echo $translator->translate('quote.amounts'); ?>">
                    <option value="{{{quote_item_subtotal}}}">
                        <?php echo $translator->translate('subtotal'); ?>
                    </option>
                    <option value="{{{quote_tax_total}}}">
                        <?php echo $translator->translate('quote.tax'); ?>
                    </option>
                    <option value="{{{quote_item_discount}}}">
                        <?php echo $translator->translate('discount'); ?>
                    </option>
                    <option value="{{{quote_total}}}">
                        <?php echo $translator->translate('total'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?php echo $translator->translate('extra.information'); ?>">
                    <option value="{{{quote_guest_url}}}">
                        <?php echo $translator->translate('guest.url'); ?>
                    </option>
                </optgroup>

                <optgroup label="<?php echo $translator->translate('custom.fields'); ?>">
                    <?php
                        /**
                         * @var App\Invoice\Entity\CustomField $custom
                         */
                        foreach ($custom_fields_quote_custom as $custom) { ?>
                        <option value="{{{<?php echo 'cf_'.$custom->getId(); ?>}}}">
                            <?php echo ($custom->getLabel() ?? '').' (ID '.$custom->getId().')'; ?>
                        </option>
                    <?php } ?>
                </optgroup>
            </select>
        </div>
        