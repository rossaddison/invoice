<?php

declare(strict_types=1);

use Yiisoft\Html\Html;

/**
 * @see src\Invoice\Helpers\Telegram\TelegramHelper;
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array $body
 */
?>
<div class='row'>
    <div class="col-xs-12 col-md-8 col-md-offset-2">
        <div class="panel panel-default">
            <div class="panel-heading">
                <?= $translator->translate('telegram.bot.api.general.purpose'); ?>
            </div>
            <div class="panel-body">
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <div class="checkbox">
                                <?php $body['settings[enable_telegram]'] = $s->getSetting('enable_telegram');?>
                                <label>
                                    <input type="hidden" name="settings[enable_telegram]" value="0">
                                    <input type="checkbox" name="settings[enable_telegram]" value="1"
                                        <?php $s->check_select($body['settings[enable_telegram]'], 1, '==', true); ?>>
                                        <?= Html::a(
                                            $translator->translate('telegram.bot.api.enable'),
                                            'https://core.telegram.org/bots/api',
                                            ['style' => 'text-decoration:none','data-bs-toggle' => 'tooltip','title' => '']
                                        );
?>
                                </label>
                            </div>                            
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <p><b>Further reading: </b><a href="https://core.telegram.org/bots/api">Telegram Bot Api</a><p>
                            <p><b>Further reading: </b><a href="https://github.com/vjik/telegram-bot-api">Vjik Telegram Bot Api</a><p>
                            <p><b>1. Inside the Telegram App (mobile/desktop) search with .... </b><pre>botfather</pre></p>
                            <p><b>2. Click on the .... <pre>/newbot - create a new bot</pre>  link</b></p>
                            <p><b>3. Record the token here ...</b>
                                <label for="settings[telegram_token]" >
                                    <?= $translator->translate('telegram.bot.api.token'); ?>
                                </label>
                                <?php
                                    $body['settings[telegram_token]'] = $s->getSetting('telegram_token');
?>
                                <input type="password" name="settings[telegram_token]" id="settings[telegram_token]"
                                    class="form-control" value="<?= Html::encode($body['settings[telegram_token]']); ?>">
                            </p>
                            <p><b>4. </b>Your bot cannot send a message to itself (error 409) so inside the Telegram App, search for your bot using its username, and send it a message from your personal non-bot account.</p>
                            <p><b>5. </b>Ensure the listening webhook is off before using the manual 'getUpdates' which uses 'long polling' by <a href="<?= $urlGenerator->generate('telegram/delete_webhook'); ?>">clicking here ...</a></p>
                            <p><b>6. </b>Use 'getUpdates' now by <a href="<?= $urlGenerator->generate('telegram/get_updates'); ?>" target="_blank">clicking here ...</a> and you will see the message Chat Id and message that you sent to your Bot in <b>Step 4.</b> Record your personal account <b>Chat Id</b> below</p>
                            <p><b>7. </b>
                                <label for="settings[telegram_chat_id]" >
                                    <?= $translator->translate('telegram.bot.api.chat.id'); ?>
                                </label>
                                <?php
    $body['settings[telegram_chat_id]'] = $s->getSetting('telegram_chat_id');
?>
                                <input type="password" name="settings[telegram_chat_id]" id="settings[telegram_chat_id]"
                                    class="form-control" value="<?= Html::encode($body['settings[telegram_chat_id]']); ?>">
                            </p>
                            <label for="settings[telegram_test_message_use]">
                                <b>8. </b><?= $translator->translate('telegram.bot.api.hello.world.test.message.use'); ?>
                            </label>
                            <?php $body['settings[telegram_test_message_use]'] = $s->getSetting('telegram_test_message_use'); ?>
                            <select name="settings[telegram_test_message_use]" id="settings[telegram_test_message_use]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
        $s->check_select($body['settings[telegram_test_message_use]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                            <br>
                            <p><b>9. </b>To receive your first 'Hello' 'World' test message <a href="<?= $urlGenerator->generate('telegram/index'); ?>">click here...</a></p>
                            <label for="settings[telegram_payment_notifications]">
                                <b>10. </b><?= $translator->translate('telegram.bot.api.payment.notifications'); ?>
                            </label>
                            <?php $body['settings[telegram_payment_notifications]'] = $s->getSetting('telegram_payment_notifications'); ?>
                            <select name="settings[telegram_payment_notifications]" id="settings[telegram_payment_notifications]" class="form-control">
                                <option value="0">
                                    <?= $translator->translate('no'); ?>
                                </option>
                                <option value="1" 
                                    <?php
    $s->check_select($body['settings[telegram_payment_notifications]'], '1');
?>>
                                    <?= $translator->translate('yes'); ?>
                                </option>
                            </select>
                            <br>
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <label for="settings[telegram_payment_notifications]">
                            <b><h4><?= 'Webhooks'; ?></h4></b>
                        </label>
                        <div class="form-group">
                            <p><?= $translator->translate('telegram.bot.api.current.status'); ?></p>
                            <p><?= $translator->translate('telegram.bot.api.future.use'); ?></p>
                            <p><b>Further reading: </b><a href="https://core.telegram.org/bots/api#setwebhook">Webhook Secret Token</a><p>
                            <label for="settings[telegram_webhook_secret_token]" >
                                <?= $translator->translate('telegram.bot.api.webhook.secret.token'); ?>
                            </label>
                            <?php
                                $body['settings[telegram_webhook_secret_token]'] = $s->getSetting('telegram_webhook_secret_token');
?>
                            <input type="password" name="settings[telegram_webhook_secret_token]" id="settings[telegram_webhook_secret_token]"
                                 class="form-control" value="<?= Html::encode($body['settings[telegram_webhook_secret_token]']); ?>">
                        </div>
                    </div>
                </div>
                <div class='row'>
                    <div class="col-xs-12 col-md-6">
                        <div class="form-group">
                            <label>
                                <b><?= $translator->translate('telegram.bot.api.webhook.url.this.site'); ?></b>
                            </label>
                            <p><pre><?= $urlGenerator->generateAbsolute('telegram/webhook'); ?></pre></p>                           
                        </div>
                    </div>
                </div>    
            </div>    
        </div>
    </div>
</div>