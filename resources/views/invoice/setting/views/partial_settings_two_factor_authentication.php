<?php

declare(strict_types=1);

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<div class='row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('two.factor.authentication'); ?>
            </div>
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_tfa]'] = $s->getSetting('enable_tfa'); ?>
                                <label <?= $s->where('enable_tfa'); ?>>
                                    <input type="hidden" name="settings[enable_tfa]" value="0">
                                    <input type="checkbox" name="settings[enable_tfa]" value="1"
                                        <?php $s->check_select($body['settings[enable_tfa]'], 1, '==', true); ?>>
                                    <?= $translator->translate('two.factor.authentication.enable'); ?>
                                </label>
                            </div>    
                        </div>
                        <div class="form-group">
                            <label for="settings[enable_tfa_with_disabling]">
                                <p><?= $translator->translate('yes').' = '; ?><?= $translator->translate('two.factor.authentication.enabled.with.disabling'); ?></p>
                                <p><?= $translator->translate('no').' = '; ?><?= $translator->translate('two.factor.authentication.enabled.without.disabling'); ?></p>
                            </label>
                            <?php $body['settings[enable_tfa_with_disabling]'] = $s->getSetting('enable_tfa_with_disabling'); ?>
                            <select name="settings[enable_tfa_with_disabling]" id="settings[enable_tfa_with_disabling]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
                                        $s->check_select($body['settings[enable_tfa_with_disabling]'], '1');
                                    ?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>
