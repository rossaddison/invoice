<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\Breadcrumbs;
use Yiisoft\Bootstrap5\BreadcrumbLink;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $alert
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */
echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

if (($s->getSetting('enable_telegram') == '1') && ($s->getSetting('telegram_token') == '1')) {
    echo Breadcrumbs::widget()
         ->links(
             BreadcrumbLink::to('Telegram', $urlGenerator->generate('telegram/index', ['_language' => 'en'])),
             BreadcrumbLink::to('Set Webhook', $urlGenerator->generate('telegram/setWebhook', ['_language' => 'en'])),
             BreadcrumbLink::to('Delete Webhook', $urlGenerator->generate('telegram/deleteWebhook', ['_language' => 'en'])),
             BreadcrumbLink::to('Get Webhook info', $urlGenerator->generate('telegram/getWebhookinfo', ['_language' => 'en'])),
             BreadcrumbLink::to('Get Updates', $urlGenerator->generate('telegram/getUpdates', ['_language' => 'en'])),
         )->render();
}
