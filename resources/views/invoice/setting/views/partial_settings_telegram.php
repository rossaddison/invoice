<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
 * The Telegram tab of Settings: how to set the bot up, in the order that works.
 *
 * Four sections, eleven steps, all of the wording in the 'telegram.tab.*' translation keys:
 *   A. the bot and the webhook (steps 1 to 5)       needed for everything
 *   B. your own alerts (steps 6 and 7)              optional: low-stock and gateway alerts, the test message
 *   C. customers (steps 8 and 9)                    how they link themselves, and where "send invoice/PDF" looks
 *   D. Telegram Payments (steps 10 and 11)          optional
 *
 * Related logic: see src\Invoice\Helpers\Telegram\TelegramHelper, src\Invoice\Telegram\TelegramController,
 * src\Invoice\Telegram\TelegramWebhookGuard (the webhook secret token is required) and
 * src\Invoice\Telegram\TelegramLinkService (customers link their own Telegram).
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\FastRoute\UrlGenerator $urlGenerator
 * @var array $body
 * @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod[] $payment_methods
 */

// a translated sentence, encoded for HTML
$t = static fn (string $key): string => H::encode($translator->translate($key));

// "3. text" as a paragraph, the text from a 'telegram.tab.*' key
$step = static fn (string $number, string $key): string
    => '<p><b>' . $number . '.</b> ' . $t($key) . '</p>';

// a small outline button that opens one of the Telegram pages
$button = static fn (string $key, string $route, bool $newTab = false): string
    => H::a(
        $translator->translate($key),
        $urlGenerator->generate($route),
        ['class' => 'btn btn-outline-secondary btn-sm me-1'] + ($newTab ? ['target' => '_blank', 'rel' => 'noopener'] : []),
    )->render();

// a secret setting: shown as a password field
$secret = static fn (string $setting, string $labelKey): string
    => '<div class="mb-3"><label class="form-label" for="settings[' . $setting . ']">' . $t($labelKey) . '</label>'
    . H::input(
        'password',
        'settings[' . $setting . ']',
        $s->getSetting($setting),
        ['id' => 'settings[' . $setting . ']', 'class' => 'form-select', 'autocomplete' => 'off'],
    )->render() . '</div>';

// a Yes / No setting
$yesNo = static function (string $setting, string $labelKey) use ($s, $translator, $t): string {
    $name = 'settings[' . $setting . ']';
    $value = $s->getSetting($setting);
    return '<div class="mb-3"><label class="form-label" for="' . $name . '">' . $t($labelKey) . '</label>'
        . H::openTag('select', ['name' => $name, 'id' => $name, 'class' => 'form-select'])
        . new Option()->value('0')->selected($value == '0')->content($translator->translate('no'))->render()
        . new Option()->value('1')->selected($value == '1')->content($translator->translate('yes'))->render()
        . H::closeTag('select') . '</div>';
};

$section = static fn (string $key): string => '<h5 class="mt-4 pt-3 border-top">' . $t($key) . '</h5>';

echo H::openTag('div', ['class' => 'row']);
echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']);
echo H::openTag('div', ['class' => 'card']);
echo '<div class="card-header">' . $t('telegram.bot.api.general.purpose') . '</div>';
echo H::openTag('div', ['class' => 'card-body']);

// Enable Telegram
echo '<div class="form-check mb-3">'
    . H::hiddenInput('settings[enable_telegram]', '0')->render()
    . H::checkbox('settings[enable_telegram]', '1')
        ->id('enable_telegram')
        ->addClass('form-check-input')
        ->checked($s->getSetting('enable_telegram') == '1')
        ->render()
    . '<label class="form-check-label" for="enable_telegram">'
    . H::a($translator->translate('telegram.bot.api.enable'), 'https://core.telegram.org/bots/api', ['class' => 'text-decoration-none'])->render()
    . '</label></div>';
echo '<p class="small"><b>' . $t('telegram.tab.further.reading') . '</b> '
    . H::a('Telegram Bot Api', 'https://core.telegram.org/bots/api')->render() . ' · '
    . H::a('phptg/bot-api by Sergei Predvoditelev (vjik)', 'https://github.com/phptg/bot-api')->render() . '</p>';

// A. the bot and the webhook
echo $section('telegram.tab.section.bot');
echo $step('1', 'telegram.tab.step.create.bot');
echo $step('2', 'telegram.tab.step.token');
echo $secret('telegram_token', 'telegram.bot.api.token');
echo $step('3', 'telegram.tab.step.secret');
echo $secret('telegram_webhook_secret_token', 'telegram.bot.api.webhook.secret.token');
echo $step('4', 'telegram.tab.step.app.url');
echo $step('5', 'telegram.tab.step.register');
echo '<p>' . $button('telegram.tab.button.register', 'telegram/index') . $button('telegram.tab.button.webhook.info', 'telegram/getWebhookinfo', true) . '</p>';
echo '<p class="small text-muted">' . $t('telegram.tab.webhook.address') . ' <code>'
    . H::encode($urlGenerator->generateAbsolute('telegram/webhook')) . '</code></p>';

// B. your own alerts
echo $section('telegram.tab.section.owner');
echo $step('6', 'telegram.tab.step.chat.id');
echo '<p>' . $button('telegram.tab.button.webhook.off', 'telegram/deleteWebhook')
    . $button('telegram.tab.button.get.updates', 'telegram/getUpdates', true) . '</p>';
echo $secret('telegram_chat_id', 'telegram.bot.api.chat.id');
echo $step('7', 'telegram.tab.step.test.message');
echo $yesNo('telegram_test_message_use', 'telegram.bot.api.hello.world.test.message.use');

// C. customers
echo $section('telegram.tab.section.customers');
echo $step('8', 'telegram.tab.step.customers.link');
echo $step('9', 'telegram.tab.step.client.chat.id');

// D. Telegram Payments
echo $section('telegram.tab.section.payments');
echo $step('10', 'telegram.tab.step.provider');
echo '<p class="small">' . H::a($translator->translate('telegram.tab.payments.docs'), 'https://core.telegram.org/bots/payments', ['target' => '_blank', 'rel' => 'noopener'])->render()
    . ' · ' . H::a('&#9432; ' . $translator->translate('telegram.tab.providers'), '#telegram-providers', ['data-bs-toggle' => 'modal'])->encode(false)->render() . '</p>';
echo $secret('telegram_provider_token', 'telegram.bot.api.provider.token');
echo '<div class="mb-3"><label class="form-label" for="settings[telegram_payment_method_id]">'
    . $t('telegram.bot.api.payment.method.id') . ' ('
    . H::a($translator->translate('telegram.tab.payment.methods'), $urlGenerator->generate('paymentmethod/index'))->render() . ')</label>';
$savedPmId = (int) ($s->getSetting('telegram_payment_method_id') ?: 0);
echo H::openTag('select', ['name' => 'settings[telegram_payment_method_id]', 'id' => 'settings[telegram_payment_method_id]', 'class' => 'form-select']);
foreach ($payment_methods as $paymentMethod) {
    $isDefault = $savedPmId === 0 && str_contains($paymentMethod->getName() ?? '', 'Card / Direct Debit');
    echo new Option()
        ->value((string) $paymentMethod->reqId())
        ->selected($savedPmId === $paymentMethod->reqId() || $isDefault)
        ->content($paymentMethod->getName() ?? '')
        ->render();
}
echo H::closeTag('select') . '</div>';
echo $step('11', 'telegram.tab.step.send.invoice');

echo H::closeTag('div'); // card-body
echo H::closeTag('div'); // card
echo H::closeTag('div'); // col
echo H::closeTag('div'); // row

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
