<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Button;

/**
 * @see https://github.com/MicrosoftDocs/dynamics365smb-docs/blob/main/business-central/LocalFunctionality/UnitedKingdom/fraud-prevention-data.md
 * @see ...src\Invoice\Asset\rebuild\js\setting.js btn-fph-generate
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */
?>
<div class = 'row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= A::tag()->href('https://github.com/MicrosoftDocs/dynamics365smb-docs/blob/main/business-central/LocalFunctionality/UnitedKingdom/fraud-prevention-data.md')->content($translator->translate('mtd.fph')); ?>
            </div>
            <div class="panel-body">
                <br>
                <label>
                    <h4>
                        <?= $translator->translate('mtd.gov.client.connection.method'); ?>
                    </h4>
                </label>
                <div class = 'row'>
                    <div class="form-group">
        <!-- Connection Method -->               
                        <?php
                            $body['settings[fph_connection_method]'] = $s->getSetting('fph_connection_method'); ?>
                        <input type="text" name="settings[fph_connection_method]" id="settings[fph_connection_method]"
                            class="form-control" readonly
                            value="<?= strlen($body['settings[fph_connection_method]']) > 0 ? $body['settings[fph_connection_method]'] : 'WEB_APP_VIA_SERVER'; ?>">
                        <label>
                            <h4>
                                <?= $translator->translate('mtd.gov.client.browser.js.user.agent') . ' ' . $translator->translate('mtd.gov.client.browser.js.user.agent.eg'); ?>
                            </h4>
                        </label>
                        
        <!-- Client Browser User Agent -->               
                        <?php
                            $body['settings[fph_client_browser_js_user_agent]'] = $s->getSetting('fph_client_browser_js_user_agent'); ?>
                        <input type="text" name="settings[fph_client_browser_js_user_agent]" id="settings[fph_client_browser_js_user_agent]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_client_browser_js_user_agent]']; ?>">
        
        <!-- Client Device Id -->                
                        <label>
                            <h4>
                                <?= $translator->translate('mtd.gov.client.device.id') . ' ' . $translator->translate('mtd.gov.client.device.id.eg'); ?>
                            </h4>
                        </label>
                        
                        <?php
                            $body['settings[fph_client_device_id]'] = $s->getSetting('fph_client_device_id'); ?>
                        <input type="text" name="settings[fph_client_device_id]" id="settings[fph_client_device_id]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_client_device_id]']; ?>">
        
        <!-- Client Screens -->                
                        <label>
                            <h4>
                                <?= $translator->translate('mtd.gov.client.screens'); ?>
                            </h4>
                        </label>
                        <br>
                        <label for="settings[fph_screen_width]">
                                <?= $translator->translate('mtd.gov.client.screens.width').' (' . $translator->translate('mtd.gov.client.screens.pixels') .')'; ?>
                        </label>
                        <?php
                            $body['settings[fph_screen_width]'] = $s->getSetting('fph_screen_width'); ?>
                        <input type="text" name="settings[fph_screen_width]" id="settings[fph_screen_width]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_screen_width]']; ?>">
                        <label for="settings[fph_screen_height]">
                            <?= $translator->translate('mtd.gov.client.screens.height') . ' (' . $translator->translate('mtd.gov.client.screens.pixels') .')'; ?>
                        </label>
                        <?php
                            $body['settings[fph_screen_height]'] = $s->getSetting('fph_screen_height'); ?>
                        <input type="text" name="settings[fph_screen_height]" id="settings[fph_screen_height]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_screen_height]']; ?>">
                        <label for="settings[fph_screen_scaling_factor]">
                            <?= $translator->translate('mtd.gov.client.screens.scaling.factor') . ' (' . $translator->translate('mtd.gov.client.screens.scaling.factor.bits') . ')'; ?>
                        </label>
                        <?php
                            $body['settings[fph_screen_scaling_factor]'] = $s->getSetting('fph_screen_scaling_factor'); ?>
                        <input type="text" name="settings[fph_screen_scaling_factor]" id="settings[fph_screen_scaling_factor]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_screen_scaling_factor]']; ?>">
                        <label for="settings[fph_screen_colour_depth]">
                            <?= $translator->translate('mtd.gov.client.screens.colour.depth'); ?>
                        </label>
                        <?php
                            $body['settings[fph_screen_colour_depth]'] = $s->getSetting('fph_screen_colour_depth'); ?>
                        <input type="text" name="settings[fph_screen_colour_depth]" id="settings[fph_screen_colour_depth]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_screen_colour_depth]']; ?>">
                        <label for="settings[fph_timestamp]">
                            <?= $translator->translate('mtd.fph.screen.timestamp'); ?>
                        </label>
                        <?php
                            $body['settings[fph_timestamp]'] = $s->getSetting('fph_timestamp'); ?>
                        <input type="text" name="settings[fph_timestamp]" id="settings[fph_timestamp]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_timestamp]']; ?>">
        <!-- Client Window Size -->
                        <label for="settings[fph_window_size]">
                            <h4>
                                <?= $translator->translate('mtd.gov.client.window.size').' (' . $translator->translate('mtd.gov.client.window.size.pixels') .')'; ?>
                            </h4>     
                        </label>
                        <?php
                            $body['settings[fph_window_size]'] = $s->getSetting('fph_window_size'); ?>
                        <input type="text" name="settings[fph_window_size]" id="settings[fph_window_size]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_window_size]']; ?>">
        <!-- Client User Id -->
                        <label for="settings[fph_gov_client_user_id]">
                            <h4>
                                <?= $translator->translate('mtd.gov.client.user.ids').' (' . $translator->translate('mtd.gov.client.user.ids.uuid') .')'; ?>
                            </h4>     
                        </label>
                        <?php
                            $body['settings[fph_gov_client_user_id]'] = $s->getSetting('fph_gov_client_user_id'); ?>
                        <input type="text" name="settings[fph_gov_client_user_id]" id="settings[fph_gov_client_user_id]"
                            class="form-control" readonly
                            value="<?= $body['settings[fph_gov_client_user_id]']; ?>">                
                    <?= Button::tag()
                        ->id('btn_fph_generate')
                        ->addAttributes(['type' => 'reset', 'name' => 'btn_fph_generate'])                            
                        ->addAttributes([
                            'onclick' => 'return confirm("'. $translator->translate('mtd.fph.record.alert'). '")',
                        ])
                        ->addClass('btn btn-success me-1')
                        ->content($translator->translate('mtd.fph.generate'))
                        ->render();
                    ?>    
                    </div>
                </div>                
            </div>
        </div>   
    </div>    
</div>
