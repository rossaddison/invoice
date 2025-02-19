<?php

declare(strict_types=1);

use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Label;
use Yiisoft\Html\Tag\Br;
use Yiisoft\Html\Tag\I;

/**
 * @var string $alert
 * @var Vjik\TelegramBot\Api\FailResult|array $updates
 */

echo $alert;
echo I::tag()->content('')->addClass('bi bi-info-circle')->addAttributes(['data-bs-toggle' => 'tooltip','title' => '.../resources/views/telegram/updates.php'])->render();
if (!$updates instanceof \Vjik\TelegramBot\Api\FailResult) {
    /**
     * @var Vjik\TelegramBot\Api\Type\Update\Update $update
     */
    foreach ($updates as $update) {
        echo $update->getRaw();
        echo Html::opentag('pre');
        $message = $update->message;
        if (null!==$message) {
            echo Label::tag()->content('Chat Id: '.(string)$message->chat->id)->render();
            echo Br::tag()->render();
            echo Label::tag()->content('Chat Username: '. ($message->chat->username ?? '????'))->render();
            echo Br::tag()->render();
            echo Label::tag()->content('Chat First Name: '.($message->chat->firstName ?? '????'))->render();
            echo Br::tag()->render();
            echo Label::tag()->content('Chat Last Name: '.($message->chat->lastName ?? '????'))->render();
            echo Br::tag()->render();
            echo Label::tag()->content('Chat Message: '.($message->text ?? '????'))->render();
        } 
        echo Html::closeTag('pre');
    }
}
?>