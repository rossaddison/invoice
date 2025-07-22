<?php

declare(strict_types=1);

/**
 * @var App\Invoice\SalesOrder\SalesOrderForm  $form
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $customFields
 * @var array                                  $customValues
 * @var array                                  $salesOrderCustomValues
 * */
?>

<?php if ($customFields) { ?>
        <div>
            <div class="mb-3 form-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?php echo $translator->translate('custom.fields'); ?>
                    </div>
                    <div>
                        <div class='row'>
                            <div class="form-group">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\CustomField
                                     */
                                    foreach ($customFields as $customField) { ?>
                                    <?php if (0 != $customField->getLocation()) {
                                        continue;
                                    } ?>
                                    <?php ++$i; ?>
                                    <?php if (0 != $i % 2) { ?>
                                        <?php $cvH->print_field_for_view($customField, $form, $salesOrderCustomValues, $customValues); ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                            <div class="form-group">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\CustomField
                                     */
                                    foreach ($customFields as $customField) { ?>
                                    <?php if (0 !== $customField->getLocation()) {
                                        continue;
                                    } ?>
                                    <?php ++$i; ?>
                                    <?php if (0 == $i % 2) { ?>
                                        <?php $cvH->print_field_for_view($customField, $form, $salesOrderCustomValues, $customValues); ?>
                                    <?php } ?>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php } ?>