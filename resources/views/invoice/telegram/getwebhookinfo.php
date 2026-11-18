<?php

declare(strict_types=1);

use App\Invoice\Telegram\WebhookInfoReport;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $alert
 * @var Phptg\BotApi\Type\Update\WebhookInfo|Phptg\BotApi\FailResult $webhookinfo
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

echo  new I()->content('')->addClass('bi bi-info-circle')->addAttributes(['data-bs-toggle' => 'tooltip','title' => '.../resources/views/telegram/getwebhookinfo.php'])->render();
if (!$webhookinfo instanceof \Phptg\BotApi\FailResult) {
    // What Telegram reports (address, pending updates, last error) and, first, a warning when no Webhook
    // Secret Token is set. The wording lives in App\Invoice\Telegram\WebhookInfoReport, where it is tested.
    echo Html::openTag('pre');
    foreach (WebhookInfoReport::lines($webhookinfo, $s->getSetting('telegram_webhook_secret_token') !== '') as $line) {
        echo Html::span($line['text'], $line['warning'] ? ['class' => 'text-danger fw-bold'] : [])->render();
        echo "\n";
    }
    echo Html::closeTag('pre');
}
