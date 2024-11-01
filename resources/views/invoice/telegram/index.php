<?php

declare(strict_types=1);

use Yiisoft\Yii\Bootstrap5\Breadcrumbs;
use Yiisoft\Yii\Bootstrap5\Link;

/**
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

echo Breadcrumbs::widget()
     ->links(
        new Link('Home', '/'),
        (new Link('Set Webhook', 'set_webhook' ))
         ->attributes(['template' => "<span class=\"testMe\">{link}</span>\n"]),
        new Link('Delete Webhook', $urlGenerator->generate('telegram/delete_webhook', ['_language' => 'en'])),
        new Link('Get Webhook info', $urlGenerator->generate('telegram/get_webhookinfo', ['_language' => 'en'])),
        new Link('Get Updates', $urlGenerator->generate('telegram/get_updates', ['_language' => 'en'])),
    )->render();

/**
 * @var string $alert
 */
echo $alert;

?>