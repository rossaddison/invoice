<?php
    declare(strict_types=1);
?>
<div class="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('invoice.invoice.vat'); ?>
            </div>
            <div class="panel-body">
                
                <div class="row">
                    <div class="col-xs-12 col-md-6">
                        
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_vat_registration]'] = $s->get_setting('enable_vat_registration');?>
                                <label for="settings[enable_vat_registration]" <?= $s->where('enable_vat_registration'); ?>">
                                    <input type="hidden" name="settings[enable_vat_registration]" value="0">
                                    <input type="checkbox" name="settings[enable_vat_registration]" value="1"
                                        <?php $s->check_select($body['settings[enable_vat_registration]'], 1, '==', true) ?>>
                                    <?= $translator->translate('invoice.invoice.enable.vat'); ?>
                                </label>
                            </div>
                            <div class="checkbox">
                                <?php $body['settings[display_vat_enabled_message]'] = $s->get_setting('display_vat_enabled_message');?>
                                <label for="settings[display_vat_enabled_message]">
                                    <input type="hidden" name="settings[display_vat_enabled_message]" value="0">
                                    <input type="checkbox" name="settings[display_vat_enabled_message]" value="1"
                                        <?php $s->check_select($body['settings[display_vat_enabled_message]'], 1, '==', true) ?>>
                                    <?= $translator->translate('invoice.invoice.enable.vat.message'); ?>
                                </label>
                                <br>
                                <br>
                                <p><?= $translator->translate('invoice.invoice.enable.vat.warning.line.1'); ?></p>
                                <p><?= $translator->translate('invoice.invoice.enable.vat.warning.line.2'); ?></p>
                                <p><?= $translator->translate('invoice.invoice.enable.vat.warning.line.3'); ?></p>
                                <p><?= $translator->translate('invoice.invoice.enable.vat.warning.line.4'); ?></p>                                  </p>
                            </div>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>
