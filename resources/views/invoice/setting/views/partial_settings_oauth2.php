<?php

declare(strict_types=1);

use Yiisoft\Html\Tag\I;

/**
 * @see src\Auth\controller\AuthController
 * @see src\Auth\Trait\Oauth2
 * @see src\Auth\Controller\SignupController
 * @see App\Widget\Button
 * @see resources\views\auth\login
 * @see resource\views\signup\signup
 * @see App\Invoice\InvoiceController no_developer_sandbox_hmrc_continue_button
 * @see App\Invoice\Setting\SettingController function tab_index oauth2
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 */
?>
<div class ="row">
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <label <?= $s->where('oauth2'); ?>><i class="bi bi-info-circle"></i>
                    <?= $translator->translate('oauth2') . ' '.'⛔'; ?>
                </label>    
            </div>
            <div class="panel-body">
                <div class="form-group">
                    <div class="checkbox">
                        <?php $body['settings[no_developer_sandbox_hmrc_continue_button]'] = $s->getSetting('no_developer_sandbox_hmrc_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_developer_sandbox_hmrc_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_developer_sandbox_hmrc_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_developer_sandbox_hmrc_continue_button]'], 1, '==', true) ?>>
                            <img src="/img/govuk-opengraph-image.png" width="12" height="12"><?= chr(32).$translator->translate('gov.developer.sandbox').chr(32).$translator->translate('gov.developer.sandbox.uk'); ?>
                        </label>
                    </div>    
                    <div class="checkbox">
                        <?php $body['settings[no_github_continue_button]'] = $s->getSetting('no_github_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_github_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_github_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_github_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-github')->render() . ' Github'; ?>
                        </label>
                    </div>                
                    <div class="checkbox">
                        <?php $body['settings[no_google_continue_button]'] = $s->getSetting('no_google_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_google_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_google_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_google_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-google')->render() . ' Google'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_facebook_continue_button]'] = $s->getSetting('no_facebook_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_facebook_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_facebook_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_facebook_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-facebook')->render() . ' Facebook'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_govuk_continue_button]'] = $s->getSetting('no_govuk_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_govuk_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_govuk_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_govuk_continue_button]'], 1, '==', true) ?>>
                            <img src="/img/govuk-opengraph-image.png" width="12" height="12"><?= ' Gov Uk'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_linkedin_continue_button]'] = $s->getSetting('no_linkedin_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_linkedin_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_linkedin_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_linkedin_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-linkedin')->render() . ' LinkedIn'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_microsoftonline_continue_button]'] = $s->getSetting('no_microsoftonline_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_microsoftonline_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_microsoftonline_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_microsoftonline_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-microsoft')->render() . ' Microsoft Online'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_vkontakte_continue_button]'] = $s->getSetting('no_vkontakte_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_vkontakte_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_vkontakte_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_vkontakte_continue_button]'], 1, '==', true) ?>>
                            <img src="/img/vkontakte.jpg" width="12" height="12"><?= ' VKontakte'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_x_continue_button]'] = $s->getSetting('no_x_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_x_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_x_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_x_continue_button]'], 1, '==', true) ?>>
                            <?= I::tag()->addClass('bi bi-twitter')->render() . ' X i.e Twitter'; ?>
                        </label>
                    </div>
                    <div class="checkbox">
                        <?php $body['settings[no_yandex_continue_button]'] = $s->getSetting('no_yandex_continue_button');?>
                        <label>
                            <input type="hidden" name="settings[no_yandex_continue_button]" value="0">
                            <input type="checkbox" name="settings[no_yandex_continue_button]" value="1"
                                <?php $s->check_select($body['settings[no_yandex_continue_button]'], 1, '==', true) ?>>
                            <img src="/img/yandex.jpg" width="12" height="12"><?= ' Yandex'; ?>
                        </label>
                    </div>
                </div>
            </div>    
        </div>
    </div>
</div>