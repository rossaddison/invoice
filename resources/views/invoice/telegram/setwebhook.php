<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\I;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var string $alert
 * @var Phptg\BotApi\Type\Update\WebhookInfo|Phptg\BotApi\FailResult $webhookinfo
 */

echo $s->getSetting('disable_flash_messages') == '0' ? $alert : '';

if (!$webhookinfo instanceof \Phptg\BotApi\FailResult) {
    echo  new I()->content('')->addClass('bi bi-info-circle')->addAttributes(['data-bs-toggle' => 'tooltip','title' => '.../resources/views/telegram/webhook.php'])->render();
    echo Html::opentag('pre');
    echo  new Label()->content(empty($webhookinfo->url)
           ? 'Your url is an empty string which shows that you are using getUpdates which mutually excludes webhook use.'
           : 'Here is your currently setup webhook url: ' . $webhookinfo->url)->render();
    echo  new Br()->render();
    echo  new Label()->content('Pending Update Count: ' . (string) $webhookinfo->pendingUpdateCount)->render();
    echo  new Br()->render();
    echo Html::closeTag('pre');
}
