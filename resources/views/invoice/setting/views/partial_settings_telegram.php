<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* Related logic: see src\Invoice\Helpers\Telegram\TelegramHelper;
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
* @var array $body
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-xs-12 col-md-6'];
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = ['class' => 'form-control'];
$checkbox = ['class' => 'checkbox'];

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo $translator->translate(
     'telegram.bot.api.general.purpose'
    );
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('div', $checkbox); //8
        $body['settings[enable_telegram]'] = 
        $s->getSetting('enable_telegram');
        echo H::openTag('label');
         echo H::openTag('input', [
          'type' => 'hidden',
          'name' => 'settings[enable_telegram]',
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'name' => 'settings[enable_telegram]',
          'value' => '1',
          'checked' => (
          $body['settings[enable_telegram]']
          == '1') ? 'checked' : null
         ]);
         echo H::a(
          $translator->translate(
          'telegram.bot.api.enable'
         ),
         'https://core.telegram.org/bots/api',
         [
          'style' => 'text-decoration:none',
          'data-bs-toggle' => 'tooltip',
          'title' => ''
         ]
         );
        echo H::closeTag('label');
       echo H::closeTag('div'); //8
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 
         'https://core.telegram.org/bots/api'
        ]);
         echo 'Telegram Bot Api';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 
         'https://github.com/vjik/telegram-bot-api'
        ]);
         echo 'Phptg Bot Api';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '1. Inside the Telegram App ';
         echo '(mobile/desktop) search with .... ';
        echo H::closeTag('b');
        echo H::openTag('pre');
         echo 'botfather';
        echo H::closeTag('pre');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '2. Click on the .... ';
        echo H::closeTag('b');
        echo H::openTag('pre');
         echo '/newbot - create a new bot';
        echo H::closeTag('pre');
        echo '  link';
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '3. Record the token here ...';
        echo H::closeTag('b');
        echo H::openTag('label', [
         'for' => 'settings[telegram_token]'
        ]);
         echo $translator->translate(
          'telegram.bot.api.token'
         );
        echo H::closeTag('label');
        $body['settings[telegram_token]'] = 
        $s->getSetting('telegram_token');
        echo H::openTag('input', [
         'type' => 'password',
         'name' => 'settings[telegram_token]',
         'id' => 'settings[telegram_token]',
         'class' => 'form-control',
         'value' => H::encode(
         $body['settings[telegram_token]']
        )
        ]);
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '4. ';
        echo H::closeTag('b');
        echo 'Your bot cannot send a message to ';
        echo 'itself (error 409) so inside the ';
        echo 'Telegram App, search for your bot ';
        echo 'using its username, and send it a ';
        echo 'message from your personal non-bot ';
        echo 'account.';
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '5. ';
        echo H::closeTag('b');
        echo 'Ensure the listening webhook is off ';
        echo 'before using the manual \'getUpdates\' ';
        echo 'which uses \'long polling\' by ';
        echo H::openTag('a', [
         'href' => $urlGenerator->generate(
         'telegram/delete_webhook'
        )
         ]);
         echo 'clicking here ...';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '6. ';
        echo H::closeTag('b');
        echo 'Use \'getUpdates\' now by ';
        echo H::openTag('a', [
         'href' => $urlGenerator->generate(
         'telegram/get_updates'
        ),
         'target' => '_blank'
         ]);
         echo 'clicking here ...';
        echo H::closeTag('a');
        echo ' and you will see the message Chat ';
        echo 'Id and message that you sent to your ';
        echo 'Bot in ';
        echo H::openTag('b');
         echo 'Step 4.';
        echo H::closeTag('b');
        echo ' Record your personal account ';
        echo H::openTag('b');
         echo 'Chat Id';
        echo H::closeTag('b');
        echo ' below';
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '7. ';
        echo H::closeTag('b');
        echo H::openTag('label', [
         'for' => 'settings[telegram_chat_id]'
        ]);
         echo $translator->translate(
          'telegram.bot.api.chat.id'
         );
        echo H::closeTag('label');
        $body['settings[telegram_chat_id]'] = 
        $s->getSetting('telegram_chat_id');
        echo H::openTag('input', [
         'type' => 'password',
         'name' => 'settings[telegram_chat_id]',
         'id' => 'settings[telegram_chat_id]',
         'class' => 'form-control',
         'value' => H::encode(
         $body['settings[telegram_chat_id]']
        )
        ]);
       echo H::closeTag('p');
       echo H::openTag('label', [
        'for' => 'settings[telegram_test_message_use]'
       ]);
        echo H::openTag('b');
         echo '8. ';
        echo H::closeTag('b');
        echo $translator->translate(
         'telegram.bot.api.hello.world.test.message.use'
        );
       echo H::closeTag('label');
       $body['settings[telegram_test_message_use]'] = 
       $s->getSetting('telegram_test_message_use');
       echo H::openTag('select', [
        'name' => 'settings[telegram_test_message_use]',
        'id' => 'settings[telegram_test_message_use]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->selected(
          $body[
          'settings[telegram_test_message_use]'
         ] == '0'
        )
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body[
          'settings[telegram_test_message_use]'
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
       echo H::openTag('br');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '9. ';
        echo H::closeTag('b');
        echo 'To receive your first \'Hello\' ';
        echo '\'World\' test message ';
        echo H::openTag('a', [
         'href' => $urlGenerator->generate(
         'telegram/index'
        )
         ]);
         echo 'click here...';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('label', [
        'for' => 
        'settings[telegram_payment_notifications]'
       ]);
        echo H::openTag('b');
         echo '10. ';
        echo H::closeTag('b');
        echo $translator->translate(
         'telegram.bot.api.payment.notifications'
        );
       echo H::closeTag('label');
       $body['settings[telegram_payment_notifications]'] = 
       $s->getSetting(
        'telegram_payment_notifications'
       );
       echo H::openTag('select', [
        'name' => 
        'settings[telegram_payment_notifications]',
        'id' => 
        'settings[telegram_payment_notifications]',
        'class' => 'form-control'
       ]);
        echo (new Option())
         ->value('0')
         ->selected(
          $body[
          'settings[telegram_payment_notifications]'
         ] == '0'
        )
         ->content($translator->translate('no'));
        echo (new Option())
         ->value('1')
         ->selected(
          $body[
          'settings[telegram_payment_notifications]'
         ] == '1'
        )
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
       echo H::openTag('br');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('label', [
       'for' => 
       'settings[telegram_payment_notifications]'
      ]);
       echo H::openTag('b');
        echo H::openTag('h4');
         echo 'Webhooks';
        echo H::closeTag('h4');
       echo H::closeTag('b');
      echo H::closeTag('label');
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('p');
        echo $translator->translate(
         'telegram.bot.api.current.status'
        );
       echo H::closeTag('p');
       echo H::openTag('p');
        echo $translator->translate(
         'telegram.bot.api.future.use'
        );
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo 'Further reading: ';
        echo H::closeTag('b');
        echo H::openTag('a', [
         'href' => 
         'https://core.telegram.org/bots/api#setwebhook'
        ]);
         echo 'Webhook Secret Token';
        echo H::closeTag('a');
       echo H::closeTag('p');
       echo H::openTag('label', [
        'for' => 
        'settings[telegram_webhook_secret_token]'
       ]);
        echo $translator->translate(
         'telegram.bot.api.webhook.secret.token'
        );
       echo H::closeTag('label');
       $body['settings[telegram_webhook_secret_token]'] = 
       $s->getSetting(
        'telegram_webhook_secret_token'
       );
       echo H::openTag('input', [
        'type' => 'password',
        'name' => 
        'settings[telegram_webhook_secret_token]',
        'id' => 
        'settings[telegram_webhook_secret_token]',
        'class' => 'form-control',
        'value' => H::encode(
        $body[
        'settings[telegram_webhook_secret_token]'
       ]
       )
       ]);
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label');
        echo H::openTag('b');
         echo $translator->translate(
          'telegram.bot.api.webhook.url.this.site'
         );
        echo H::closeTag('b');
       echo H::closeTag('label');
       echo H::openTag('p');
        echo H::openTag('pre');
         echo $urlGenerator->generateAbsolute(
          'telegram/webhook'
         );
        echo H::closeTag('pre');
       echo H::closeTag('p');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
