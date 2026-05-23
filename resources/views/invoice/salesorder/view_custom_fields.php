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
            <div class="mb-3">
                <div class="card">
                    <div class="card-header">
                        <?= $translator->translate('custom.fields'); ?>
                    </div>
                    <div>
                        <div class='row'>
                            <div class="mb-3">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Infrastructure\Persistence\CustomField\CustomField
                                     */
                                    foreach ($customFields as $customField): ?>
                                    <?php if ($customField->getLocation() != 0) {
                                        continue;
                                    } ?>
                                    <?php $i++; ?>
                                    <?php if ($i % 2 != 0): ?>
                                        <?php $cvH->printFieldForView($customField, $form, $salesOrderCustomValues); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-3">
                                <?php $i = 0; ?>
                                <?php
                                    /**
                                     * @var App\Infrastructure\Persistence\CustomField\CustomField
                                     */
                                    foreach ($customFields as $customField): ?>
                                    <?php if ($customField->getLocation() !== 0) {
                                        continue;
                                    } ?>
                                    <?php $i++; ?>
                                    <?php if ($i % 2 == 0): ?>
                                        <?php $cvH->printFieldForView($customField, $form, $salesOrderCustomValues); ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php endif; ?>