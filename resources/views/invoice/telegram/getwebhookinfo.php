<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\I;

/**
 * @var string $alert
 * @var Phptg\BotApi\Type\Update\WebhookInfo|Phptg\BotApi\FailResult $webhookinfo
 */

echo $alert;

echo I::tag()->content('')->addClass('bi bi-info-circle')->addAttributes(['data-bs-toggle' => 'tooltip','title' => '.../resources/views/telegram/getwebhookinfo.php'])->render();
if (!$webhookinfo instanceof \Phptg\BotApi\FailResult) {
    echo Html::opentag('pre');
    echo Label::tag()->content(empty($webhookinfo->url)
           ? 'Your url is an empty string which shows that you are using getUpdates which mutually excludes webhook use.'
           : 'Here is your currently setup webhook url: ' . $webhookinfo->url)->render();
    echo Br::tag()->render();
    echo Label::tag()->content('Pending Update Count: ' . (string) $webhookinfo->pendingUpdateCount)->render();
    echo Br::tag()->render();
    echo Html::closeTag('pre');
}
