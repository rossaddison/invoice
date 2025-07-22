<?php
declare(strict_types=1);

use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var array $body
 */

$fonts = [
    'Arial',
    'Helvetica',
    'Times New Roman',
    'Courier New',
    'Verdana',
    'Georgia',
    'Palatino',
    'Garamond',
    'Trebuchet MS',
    'Impact',
    'PT Sans',
    'PT Serif',
    'Roboto',
];
$fontSizes = ['5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20'];

?>
<div class = 'row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= I::tag()->addClass('bi bi-bootstrap')->render(); ?>
            </div>
            <div class="panel-body">
                <div class = 'row'>
                    <div class = "border border-1 border-primary">
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <div class="checkbox">
                                    <?php $body['settings[bootstrap5_offcanvas_enable]'] = $s->getSetting('bootstrap5_offcanvas_enable');?>
                                    <label <?= $s->where('bootstrap5_offcanvas_enable'); ?>">
                                        <input type="hidden" name="settings[bootstrap5_offcanvas_enable]" value="0">
                                        <input type="checkbox" name="settings[bootstrap5_offcanvas_enable]" value="1"
                                            <?php $s->check_select($body['settings[bootstrap5_offcanvas_enable]'], 1, '==', true) ?>>
                                        <?= $translator->translate('bootstrap5.offcanvas.enable'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_offcanvas_placement]" <?= $s->where('bootstrap5_offcanvas_placement'); ?> >
                                    <?= $translator->translate('bootstrap5.offcanvas.placement'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_offcanvas_placement]'] = $s->getSetting('bootstrap5_offcanvas_placement'); ?>
                                <select name="settings[bootstrap5_offcanvas_placement]" id="settings[bootstrap5_offcanvas_placement]" class="form-control">
                                    <option value="0"><?= $translator->translate('none'); ?></option>
                                    <?php
                                        $placements = ['top', 'bottom', 'start', 'end'];
/**
 * @var string $placement
 */
foreach ($placements as $placement) { ?>
                                        <option value="<?= $placement; ?>" <?php $s->check_select($body['settings[bootstrap5_offcanvas_placement]'], $placement) ?>>
                                            <?= ucfirst($placement); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class ="border"></div>
                    
                    <div class = "border border-1 border-warning">
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_alert_message_font]" <?= $s->where('bootstrap5_alert_message_font'); ?> >
                                    <?= $translator->translate('bootstrap5.alert.message.font'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_alert_message_font]'] = $s->getSetting('bootstrap5_alert_message_font'); ?>
                                <select name="settings[bootstrap5_alert_message_font]" id="settings[bootstrap5_alert_message_font]" class="form-control">
                                    <option value="0"><?= 'Arial'; ?></option>
                                    <?php
/**
 * @var string $font
 */
foreach ($fonts as $font) { ?>
                                        <option value="<?= $font; ?>" <?php $s->check_select($body['settings[bootstrap5_alert_message_font]'], $font); ?>>
                                            <?= $font; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div> 
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_alert_message_font_size]" <?= $s->where('bootstrap5_alert_message_font_size'); ?> >
                                    <?= $translator->translate('bootstrap5.alert.message.font.size'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_alert_message_font_size]'] = $s->getSetting('bootstrap5_alert_message_font_size'); ?>
                                <select name="settings[bootstrap5_alert_message_font_size]" id="settings[bootstrap5_alert_message_font_size]" class="form-control">
                                    <option value="0"><?= '10'; ?></option>
                                    <?php
/**
 * @var string $fontSize
 */
foreach ($fontSizes as $fontSize) { ?>
                                        <option value="<?= $fontSize; ?>" <?php $s->check_select($body['settings[bootstrap5_alert_message_font_size]'], $fontSize) ?>>
                                            <?= $fontSize; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>    
                        <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_alert_close_button_font_size]" <?= $s->where('bootstrap5_alert_close_button_font_size'); ?> >
                                    <?= $translator->translate('bootstrap5.alert.close.button.font.size'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_alert_close_button_font_size]'] = $s->getSetting('bootstrap5_alert_close_button_font_size'); ?>
                                <select name="settings[bootstrap5_alert_close_button_font_size]" id="settings[bootstrap5_alert_close_button_font_size]" class="form-control">
                                    <option value="0"><?= '10'; ?></option>
                                    <?php
/**
 * @var string $fontSize
 */
foreach ($fontSizes as $fontSize) { ?>
                                        <option value="<?= $fontSize; ?>" <?php $s->check_select($body['settings[bootstrap5_alert_close_button_font_size]'], $fontSize) ?>>
                                            <?= $fontSize; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>                        
                    </div>
                    
                    <div class ="border"></div>
                    
                    <div class="border border-line-1 border-danger">
                       <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_layout_invoice_navbar_font]" <?= $s->where('bootstrap5_layout_invoice_navbar_font'); ?> >
                                    <?= $translator->translate('bootstrap5.layout.invoice.navbar.font'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_layout_invoice_navbar_font]'] = $s->getSetting('bootstrap5_layout_invoice_navbar_font'); ?>
                                <select name="settings[bootstrap5_layout_invoice_navbar_font]" id="settings[bootstrap5_layout_invoice_navbar_font]" class="form-control">
                                    <option value="0"><?= 'Arial'; ?></option>
                                    <?php
/**
 * @var string $font
 */
foreach ($fonts as $font) { ?>
                                        <option value="<?= $font; ?>" <?php $s->check_select($body['settings[bootstrap5_layout_invoice_navbar_font]'], $font); ?>>
                                            <?= $font; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                       </div> 
                       <div class="col-xs-12 col-md-6">
                            <div class="form-group">
                                <label for="settings[bootstrap5_layout_invoice_navbar_font_size]" <?= $s->where('bootstrap5_alert_message_font_size'); ?> >
                                    <?= $translator->translate('bootstrap5.layout.invoice.navbar.font.size'); ?>
                                </label>
                                <?php $body['settings[bootstrap5_layout_invoice_navbar_font_size]'] = $s->getSetting('bootstrap5_layout_invoice_navbar_font_size'); ?>
                                <select name="settings[bootstrap5_layout_invoice_navbar_font_size]" id="settings[bootstrap5_layout_invoice_navbar_font_size]" class="form-control">
                                    <option value="0"><?= '10'; ?></option>
                                    <?php
/**
 * @var string $fontSize
 */
foreach ($fontSizes as $fontSize) { ?>
                                        <option value="<?= $fontSize; ?>" <?php $s->check_select($body['settings[bootstrap5_layout_invoice_navbar_font_size]'], $fontSize) ?>>
                                            <?= $fontSize; ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>