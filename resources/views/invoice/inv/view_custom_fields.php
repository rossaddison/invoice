<?php

declare(strict_types=1);

/**
 * @see InvController function view_custom_fields
 *
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\InvCustom\InvCustomForm    $invCustomForm
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array                                  $custom_fields
 * @var array                                  $custom_values
 * @var array                                  $inv_custom_values
 */
if ($custom_fields) { ?>
                    <div>
                        <div class="mb-3 form-group">
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <i tooltip="data-toggle" title="<?php echo $s->isDebugMode(4); ?>"><b><?php echo $translator->translate('custom.fields'); ?></b></i>
                                </div>
                                <div>
                                    <div class="row">
                                        <div class="form-group">
                                            <?php $i = 0; ?>
                                            <?php
                                               /**
                                                 * @var App\Invoice\Entity\CustomField $custom_field
                                                */
                                               foreach ($custom_fields as $custom_field) { ?>
                                                <?php if (0 != $custom_field->getLocation()) {
                                                    continue;
                                                } ?>
                                                <?php ++$i; ?>
                                                <?php if (0 != $i % 2) { ?>
                                                    <?php $cvH->print_field_for_view($custom_field, $invCustomForm, $inv_custom_values, $custom_values); ?>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                        <div class="form-group">
                                            <?php $i = 0; ?>
                                            <?php
                                                /**
                                                 * @var App\Invoice\Entity\CustomField $custom_field
                                                 */
                                                foreach ($custom_fields as $custom_field) { ?>
                                                <?php if (0 != $custom_field->getLocation()) {
                                                    continue;
                                                } ?>
                                                <?php ++$i; ?>
                                                <?php if (0 == $i % 2) { ?>
                                                    <?php $cvH->print_field_for_view($custom_field, $invCustomForm, $inv_custom_values, $custom_values); ?>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
<?php } ?>