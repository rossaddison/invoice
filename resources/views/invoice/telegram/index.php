<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Breadcrumbs;
use Yiisoft\Yii\Bootstrap5\Link;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $alert 
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
echo $alert;

if (($s->getSetting('enable_telegram') == '1') && ($s->getSetting('telegram_token') == '1')) {
echo Breadcrumbs::widget()
     ->links(
        new Link('Telegram', $urlGenerator->generate('telegram/index', ['_language' => 'en'])),
        new Link('Set Webhook', $urlGenerator->generate('telegram/set_webhook', ['_language' => 'en'])),
        new Link('Delete Webhook', $urlGenerator->generate('telegram/delete_webhook', ['_language' => 'en'])),
        new Link('Get Webhook info', $urlGenerator->generate('telegram/get_webhookinfo', ['_language' => 'en'])),
        new Link('Get Updates', $urlGenerator->generate('telegram/get_updates', ['_language' => 'en'])),
    )->render();
}

?>