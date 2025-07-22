<?php

use Yiisoft\Html\Html;

/*
 * This form will be used when a pdf is generated for the client.
 * @var App\Invoice\Helpers\CustomValuesHelper $cvH
 * @var App\Invoice\ClientCustom\ClientCustomForm $client_custom_form
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $client_custom_values
 * @var array $custom_values
 * @var array $custom_fields
 */

?>

<?php if ($custom_fields) { ?>
    <div>
        <div class="mb-3 form-group">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php echo $translator->translate('custom.fields'); ?>
                </div>
                <div>
                    <?php echo Html::openTag('div', ['class' => 'row']); ?>
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                               /** @var App\Invoice\Entity\CustomField $custom_field */
                               foreach ($custom_fields as $custom_field) { ?>
                                <?php if (0 != $custom_field->getLocation()) {
                                    continue;
                                } ?>
                                <?php ++$i; ?>
                                <?php if (0 != $i % 2) { ?>
                                    <?php $cvH->print_field_for_view($custom_field, $client_custom_form, $client_custom_values, $custom_values); ?>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="form-group">
                            <?php $i = 0; ?>
                            <?php
                               /** @var App\Invoice\Entity\CustomField $custom_field */
                               foreach ($custom_fields as $custom_field) { ?>
                                <?php if (0 != $custom_field->getLocation()) {
                                    continue;
                                } ?>
                                <?php ++$i; ?>
                                <?php if (0 == $i % 2) { ?>
                                    <?php $cvH->print_field_for_view($custom_field, $client_custom_form, $client_custom_values, $custom_values); ?>
                                <?php } ?>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>