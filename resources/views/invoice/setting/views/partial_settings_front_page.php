<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\A;

/**
 * @var App\Invoice\Setting\SettingRepository $s 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator 
 * @var array $body
 */
?>
<div class ="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <label <?= $s->where('front_page_file_locations_tooltip'); ?>><i class="bi bi-info-circle"></i>
                    <?= $translator->translate('invoice.invoice.front.page') . ' '.'⛔'; ?>
                </label>    
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="checkbox">
                        <?php $body['settings[no_front_about_page]'] = $s->getSetting('no_front_about_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_about_page]" value="0">
                            <input type="checkbox" name="settings[no_front_about_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_about_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.about'); ?>
                        </label>
                    </div>                
                    <div class="checkbox">
                        <?php $body['settings[no_front_accreditations_page]'] = $s->getSetting('no_front_accreditations_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_accreditations_page]" value="0">
                            <input type="checkbox" name="settings[no_front_accreditations_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_accreditations_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.accreditations'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_contact_details_page]'] = $s->getSetting('no_front_contact_details_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_contact_details_page]" value="0">
                            <input type="checkbox" name="settings[no_front_contact_details_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_contact_details_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.contact.details'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_contact_us_page]'] = $s->getSetting('no_front_contact_us_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_contact_us_page]" value="0">
                            <input type="checkbox" name="settings[no_front_contact_us_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_contact_us_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.contact.us'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_gallery_page]'] = $s->getSetting('no_front_gallery_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_gallery_page]" value="0">
                            <input type="checkbox" name="settings[no_front_gallery_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_gallery_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.gallery'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_pricing_page]'] = $s->getSetting('no_front_pricing_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_pricing_page]" value="0">
                            <input type="checkbox" name="settings[no_front_pricing_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_pricing_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.pricing'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_team_page]'] = $s->getSetting('no_front_team_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_team_page]" value="0">
                            <input type="checkbox" name="settings[no_front_team_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_team_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.team'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_testimonial_page]'] = $s->getSetting('no_front_testimonial_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_testimonial_page]" value="0">
                            <input type="checkbox" name="settings[no_front_testimonial_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_testimonial_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('menu.testimonial'); ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_front_site_slider_page]'] = $s->getSetting('no_front_site_slider_page');?>
                        <label>
                            <input type="hidden" name="settings[no_front_site_slider_page]" value="0">
                            <input type="checkbox" name="settings[no_front_site_slider_page]" value="1"
                                <?php $s->check_select($body['settings[no_front_site_slider_page]'], 1, '==', true) ?>>
                            <?= $translator->translate('invoice.home'); ?>
                        </label>
                    </div>             
                </div>
            </div>    
        </div>
    </div>
</div>