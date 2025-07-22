<?php

declare(strict_types=1);

use Yiisoft\Bootstrap5\BreadcrumbLink;
use Yiisoft\Bootstrap5\Breadcrumbs;

/**
 * @var App\Invoice\Setting\SettingRepository  $s
 * @var string                                 $alert
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface   $urlGenerator
 */
echo $alert;

if (('1' == $s->getSetting('enable_telegram')) && ('1' == $s->getSetting('telegram_token'))) {
    echo Breadcrumbs::widget()
        ->links(
            BreadcrumbLink::to('Telegram', $urlGenerator->generate('telegram/index', ['_language' => 'en'])),
            BreadcrumbLink::to('Set Webhook', $urlGenerator->generate('telegram/set_webhook', ['_language' => 'en'])),
            BreadcrumbLink::to('Delete Webhook', $urlGenerator->generate('telegram/delete_webhook', ['_language' => 'en'])),
            BreadcrumbLink::to('Get Webhook info', $urlGenerator->generate('telegram/get_webhookinfo', ['_language' => 'en'])),
            BreadcrumbLink::to('Get Updates', $urlGenerator->generate('telegram/get_updates', ['_language' => 'en'])),
        )->render();
}
