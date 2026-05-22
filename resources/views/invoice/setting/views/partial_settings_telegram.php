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
* @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod[] $payment_methods
*/

$row = ['class' => 'row'];
$colMd6 = ['class' => 'col-12 col-md-6'];
$colMd8 = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];
$checkbox = ['class' => 'form-check'];

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
       $telegram = 'settings[enable_telegram]';
       echo H::openTag('div', $checkbox); //8
        $body[$telegram] =
        $s->getSetting('enable_telegram');
        echo H::openTag('input', [
          'type' => 'hidden',
          'name' => $telegram,
          'value' => '0'
         ]);
         echo H::openTag('input', [
          'type' => 'checkbox',
          'class' => 'form-check-input',
          'id' => 'enable_telegram',
          'name' => $telegram,
          'value' => '1',
          'checked' => (
          $body[$telegram]
          == '1') ? 'checked' : null
         ]);
         echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'enable_telegram']);
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
         'https://github.com/phptg/bot-api'
        ]);
         echo 'phptg/bot-api by Sergei Predvoditelev (vjik)';
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
        echo H::openTag('label', ['for' => 'settings[telegram_token]']);
         echo $translator->translate(
          'telegram.bot.api.token'
         );
        $token = 'settings[telegram_token]';
        echo H::closeTag('label');
        $body[$token] =
        $s->getSetting('telegram_token');
        echo H::openTag('input', [
         'type' => 'password',
         'name' => $token,
         'id' => $token,
         'class' => 'form-select',
         'value' => H::encode(
         $body[$token]
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
         'telegram/deleteWebhook'
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
         'telegram/getUpdates'
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
        $chat = 'settings[telegram_chat_id]';
        echo H::openTag('label', [
         'for' => $chat
        ]);
         echo $translator->translate(
          'telegram.bot.api.chat.id'
         );
        echo H::closeTag('label');
        $body[$chat] =
        $s->getSetting('telegram_chat_id');
        echo H::openTag('input', [
         'type' => 'password',
         'name' => $chat,
         'id' => $chat,
         'class' => 'form-select',
         'value' => H::encode($body[$chat])
        ]);
       echo H::closeTag('p');
       $testMsg = 'settings[telegram_test_message_use]';
       echo H::openTag('label', [
        'for' => $testMsg
       ]);
        echo H::openTag('b');
         echo '8. ';
        echo H::closeTag('b');
        echo $translator->translate(
         'telegram.bot.api.hello.world.test.message.use'
        );
       echo H::closeTag('label');
       $body[$testMsg] =
       $s->getSetting('telegram_test_message_use');
       echo H::openTag('select', [
        'name' => $testMsg,
        'id' => $testMsg,
        'class' => 'form-select',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$testMsg] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$testMsg] == '1')
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
       $telegramPayment = 'settings[telegram_payment_notifications]';
       echo H::openTag('label', ['for' => $telegramPayment]);
        echo H::openTag('b');
         echo '10. ';
        echo H::closeTag('b');
        echo $translator->translate(
         'telegram.bot.api.payment.notifications'
        );
       echo H::closeTag('label');
       $body[$telegramPayment] =
       $s->getSetting(
        'telegram_payment_notifications'
       );
       echo H::openTag('select', [
        'name' => $telegramPayment,
        'id' => $telegramPayment,
        'class' => 'form-select',
       ]);
        echo  new Option()
         ->value('0')
         ->selected($body[$telegramPayment] == '0')
         ->content($translator->translate('no'));
        echo  new Option()
         ->value('1')
         ->selected($body[$telegramPayment] == '1')
         ->content($translator->translate('yes'));
       echo H::closeTag('select');
       echo H::openTag('br');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '11. ';
        echo H::closeTag('b');
        echo 'In @BotFather go to ';
        echo H::openTag('b');
         echo 'Payments';
        echo H::closeTag('b');
        echo ', connect your Stripe account, and paste the provider token here. ';
        echo H::openTag('a', [
         'href' => 'https://core.telegram.org/bots/payments',
         'target' => '_blank'
        ]);
         echo 'Read more...';
        echo H::closeTag('a');
       echo H::closeTag('p');
       $providerToken = 'settings[telegram_provider_token]';
       echo H::openTag('label', ['for' => $providerToken]);
        echo $translator->translate(
         'telegram.bot.api.provider.token'
        );
       echo H::closeTag('label');
       $body[$providerToken] = $s->getSetting('telegram_provider_token');
       echo H::openTag('div');
        echo H::openTag('input', [
         'type'  => 'password',
         'name'  => $providerToken,
         'id'    => $providerToken,
         'class' => 'form-select',
         'value' => H::encode($body[$providerToken])
        ]);
        echo H::openTag('a', [
         'href'           => '#telegram-providers',
         'class'          => 'btn btn-outline-secondary',
         'data-bs-toggle' => 'modal',
         'style'          => 'text-decoration:none',
        ]);
         echo '&#9432; Providers';
        echo H::closeTag('a');
       echo H::closeTag('div');
       echo H::openTag('br');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '12. ';
        echo H::closeTag('b');
        echo 'Choose which Payment Method Id (from ';
        echo H::openTag('a', [
         'href' => $urlGenerator->generate('paymentmethod/index')
        ]);
         echo 'Payment Methods';
        echo H::closeTag('a');
        echo ') will be used when a Telegram payment is recorded automatically.';
       echo H::closeTag('p');
       $pmId = 'settings[telegram_payment_method_id]';
       echo H::openTag('label', ['for' => $pmId]);
        echo $translator->translate(
         'telegram.bot.api.payment.method.id'
        );
       echo H::closeTag('label');
       $body[$pmId] = $s->getSetting('telegram_payment_method_id');
       $savedPmId = (int) ($body[$pmId] ?: 0);
       echo H::openTag('select', [
        'name'  => $pmId,
        'id'    => $pmId,
        'class' => 'form-select',
       ]);
        foreach ($payment_methods as $paymentMethod) {
            $isDefault = $savedPmId === 0
                && str_contains($paymentMethod->getName() ?? '', 'Card / Direct Debit');
            echo new Option()
             ->value((string) $paymentMethod->reqId())
             ->selected($savedPmId === $paymentMethod->reqId() || $isDefault)
             ->content(H::encode($paymentMethod->getName()));
        }
       echo H::closeTag('select');
       echo H::openTag('br');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '13. ';
        echo H::closeTag('b');
        echo 'Save these settings, then open any invoice and use ';
        echo H::openTag('b');
         echo 'Send Telegram Invoice';
        echo H::closeTag('b');
        echo ' to deliver a native Telegram payment request to the chat (set in step 7.) . ';
        echo 'When the customer pays, the webhook auto-records the payment against the invoice.';
       echo H::closeTag('p');
       echo H::openTag('br');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      $telegramNotification =
       'settings[telegram_payment_notifications]';
      echo H::openTag('label', [
       'for' => $telegramNotification
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
       $telegramToken = 'settings[telegram_webhook_secret_token]';
       echo H::openTag('label', ['for' => $telegramToken]);
        echo $translator->translate(
         'telegram.bot.api.webhook.secret.token'
        );
       echo H::closeTag('label');
       $body[$telegramToken] = $s->getSetting('telegram_webhook_secret_token');
       echo H::openTag('input', [
        'type' => 'password',
        'name' => $telegramToken,
        'id' => $telegramToken,
        'class' => 'form-select',
        'value' => H::encode($body[$telegramToken])
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

?>
<div id="telegram-providers" class="modal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Telegram Payment Providers</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>Connect your provider in <strong>@BotFather &rarr; Payments</strong> to obtain a provider token.</p>
        <table class="table table-sm table-bordered">
          <thead class="table-light">
            <tr><th>Provider</th><th>Region(s)</th><th>Notes</th></tr>
          </thead>
          <tbody>
            <?php
            $f = static fn(string $code, string $label): string
                => '<img src="https://flagcdn.com/16x12/' . $code . '.png"'
                   . ' width="16" height="12" alt="' . $code . '"'
                   . ' style="vertical-align:middle;margin-right:5px;">' . $label;
            $un = $f('un', 'Global');
            $uz = $f('uz', 'Uzbekistan');
            $ru = $f('ru', 'Russia');
            $ua = $f('ua', 'Ukraine');
            $ae = $f('ae', 'Middle East (UAE etc.)');
            $ruCis = $f('ru', 'Russia') . ' / CIS';
            ?>
            <tr><td>Stripe</td>      <td><?= $un ?></td>    <td>Most widely supported; test mode available</td></tr>
            <tr><td>Payme</td>       <td><?= $uz ?></td>    <td></td></tr>
            <tr><td>YooMoney</td>    <td><?= $ru ?></td>    <td>Formerly Yandex.Money</td></tr>
            <tr><td>Sberbank</td>    <td><?= $ru ?></td>    <td></td></tr>
            <tr><td>Tranzzo</td>     <td><?= $ua ?></td>    <td></td></tr>
            <tr><td>LiqPay</td>      <td><?= $ua ?></td>    <td></td></tr>
            <tr><td>Portmone</td>    <td><?= $ua ?></td>    <td></td></tr>
            <tr><td>Click</td>       <td><?= $uz ?></td>    <td></td></tr>
            <tr><td>Cryptomus</td>   <td><?= $un ?></td>    <td>Crypto payments</td></tr>
            <tr><td>Telr</td>        <td><?= $ae ?></td>    <td></td></tr>
            <tr><td>PayMaster</td>   <td><?= $ru ?></td>    <td></td></tr>
            <tr><td>Smartglocal</td> <td><?= $ruCis ?></td> <td></td></tr>
            <tr><td>ECOMMPAY</td>    <td><?= $un ?></td>    <td></td></tr>
          </tbody>
        </table>
        <p class="text-muted small">
          Omit the provider token and use currency <code>XTR</code> for
          <strong>Telegram Stars</strong> &mdash; no payment provider required.
        </p>
      </div>
      <div class="modal-footer">
        <a href="https://core.telegram.org/bots/payments" target="_blank"
           class="btn btn-outline-secondary btn-sm">Telegram Payments docs</a>
        <button type="button" class="btn btn-secondary btn-sm"
                data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>
<?php
