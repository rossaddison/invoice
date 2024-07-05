<?php

declare(strict_types=1);

/**
 * @var App\Invoice\SalesOrder\SalesOrderForm $form
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $customFields
 * @var array $customValues
 * @var array $salesOrderCustomValues
 * */
?>

<?php if ($customFields): ?>
        <div>
            <div class="mb-3 form-group">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <?= $translator->translate('i.custom_fields'); ?>
                    </div>
                    <div>
                        <div class='row'>
                            <div class="form-group">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\CustomField
                                     */
                                    foreach ($customFields as $customField): ?>
                                    <?php if ($customField->getLocation() != 0) {
                                        continue;
                                    } ?>
                                    <?php $i++; ?>
                                    <?php if ($i % 2 != 0): ?>
                                        <?php $cvH->print_field_for_view($customField, $form, $salesOrderCustomValues, $customValues); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-group">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Invoice\Entity\CustomField
                                     */ 
                                    foreach ($customFields as $customField): ?>
                                    <?php if ($customField->getLocation() !== 0) {
                                        continue;
                                    } ?>
                                    <?php $i++; ?>
                                    <?php if ($i % 2 == 0): ?>
                                        <?php $cvH->print_field_for_view($customField, $form, $salesOrderCustomValues, $customValues); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php endif; ?>