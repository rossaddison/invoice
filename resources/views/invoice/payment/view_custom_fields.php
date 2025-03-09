<?php

declare(strict_types=1);

/**
 * @see PaymentController function view_custom_fields and function view
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\PaymentCustom\PaymentCustomForm $paymentCustomForm
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $customFields
 * @var array $customValues
 * @var array $paymentCustomValues
 */

?>
 
<?php if ($customFields): ?>
    <div>
        <div class="mb-3 form-group">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <i tooltip="data-toggle" title="<?= $s->isDebugMode(4)?>"><?= $translator->translate('i.custom_fields'); ?></i>
                </div>
                <div>
                    <div class="row">
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                              /**
                               * @var App\Invoice\Entity\CustomField $customField
                               */
                              foreach ($customFields as $customField): ?>
                                <?php if ($customField->getLocation() != 0) {
                                    continue;
                                } ?>
                                <?php $i++; ?>
                                <?php if ($i % 2 != 0): ?>
                                    <?php $cvH->print_field_for_view($customField, $paymentCustomForm, $paymentCustomValues, $customValues); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                               /**
                                * @var App\Invoice\Entity\CustomField $customField
                                */
                                foreach ($customFields as $customField): ?>
                                <?php if ($customField->getLocation() != 0) {
                                    continue;
                                } ?>
                                <?php $i++; ?>
                                <?php if ($i % 2 == 0): ?>
                                    <?php $cvH->print_field_for_view($customField, $paymentCustomForm, $paymentCustomValues, $customValues); ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>