<?php

declare(strict_types=1);

/**
 * @see PaymentController function view_custom_fields and function view
 *
 * @var App\Invoice\Helpers\CustomValuesHelper      $cvH
 * @var App\Invoice\PaymentCustom\PaymentCustomForm $paymentCustomForm
 * @var App\Invoice\Setting\SettingRepository       $s
 * @var Yiisoft\Translator\TranslatorInterface      $translator
 * @var array                                       $customFields
 * @var array                                       $customValues
 * @var array                                       $paymentCustomValues
 */
?>
 
<?php if ($customFields) { ?>
    <div>
        <div class="mb-3 form-group">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i tooltip="data-toggle" title="<?php echo $s->isDebugMode(4); ?>"><?php echo $translator->translate('custom.fields'); ?></i>
                </div>
                <div>
                    <div class="row">
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                              /**
                               * @var App\Invoice\Entity\CustomField $customField
                               */
                              foreach ($customFields as $customField) { ?>
                                <?php if (0 != $customField->getLocation()) {
                                    continue;
                                } ?>
                                <?php ++$i; ?>
                                <?php if (0 != $i % 2) { ?>
                                    <?php $cvH->print_field_for_view($customField, $paymentCustomForm, $paymentCustomValues, $customValues); ?>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                                /**
                                 * @var App\Invoice\Entity\CustomField $customField
                                 */
                                foreach ($customFields as $customField) { ?>
                                <?php if (0 != $customField->getLocation()) {
                                    continue;
                                } ?>
                                <?php ++$i; ?>
                                <?php if (0 == $i % 2) { ?>
                                    <?php $cvH->print_field_for_view($customField, $paymentCustomForm, $paymentCustomValues, $customValues); ?>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>