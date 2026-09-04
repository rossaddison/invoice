<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Option;

/**
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var array $body
* @var array $gateway_drivers
* @var array $gateway_currency_codes
* @var array $payment_methods
* @var array<string, string> $gateway_credential_urls
* @var array<string, string> $gateway_field_urls
* @var string $tab_index_url
* @var string $adyen_hmac_verify_url
*/

$row = ['class' => 'row'];
$colMd8 = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];
$checkbox = ['class' => 'form-check'];
$pullRight = ['class' => 'float-end'];
$noMargin = ['class' => 'form-check'];
$panelBodySmall = ['class' => 'card-body small'];
$pfxGateway = 'settings[gateway_';
$sfxEnabled = '_enabled]';
$sfxCurrency = '_currency]';
$sfxLocale = '_locale]';
$sfxPaymentMethod = '_payment_method]';
echo H::openTag('div', $row); //1
echo H::openTag('div', $colMd8); //2
echo H::openTag('div', $panel); //3
echo H::openTag('div', $panelHead); //4
echo $translator->translate('online.payments');
echo H::closeTag('div'); //4
echo H::openTag('div', $panelBody); //4
echo H::openTag('div', $formGroup); //5
echo H::openTag('div', $checkbox); //6
$body['settings[enable_online_payments]'] =
$s->getSetting('enable_online_payments');
echo H::openTag('input', [
 'type' => 'hidden',
 'name' => 'settings[enable_online_payments]',
 'value' => '0'
]);
echo H::openTag('input', [
 'type' => 'checkbox',
 'class' => 'form-check-input',
 'id' => 'enable_online_payments',
 'name' => 'settings[enable_online_payments]',
 'value' => '1',
 'checked' => ($body['settings[enable_online_payments]']
 == '1') ? 'checked' : null
]);
echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'enable_online_payments']);
echo $translator->translate(
    'enable.online.payments'
);
echo H::closeTag('label');
echo H::closeTag('div'); //6
echo H::closeTag('div'); //5

echo H::openTag('div', $formGroup); //5
echo H::openTag('label', [
 'for' => 'online-payment-select'
]);
echo $translator->translate('add.payment.provider');
echo H::closeTag('label');
echo H::openTag('select', [
 'id' => 'online-payment-select',
 'class' => 'form-select',
]);
echo  new Option()
 ->value('')
 ->content($translator->translate('none'));
/**
* @var string $driver
*/
foreach (array_keys($gateway_drivers) as $driver) {
    $d = strtolower($driver);
    echo  new Option()
     ->value($d)
     ->content(ucwords(str_replace(
         '_',
         ' ',
         $driver
     )));
}
echo H::closeTag('select');
echo H::closeTag('div'); //5

echo H::closeTag('div'); //4
echo H::closeTag('div'); //3

/**
* @var string $driver
* @var array $fields
*/
foreach ($gateway_drivers as $driver => $fields) {
    $d = strtolower($driver);
    $gatewayClass = 'gateway-settings card ' .
    ($s->getSetting('gateway_' . $d . '_enabled')
     ? 'active-gateway'
     : 'hidden');
    echo H::openTag('div', [ //4
    'id' => 'gateway-settings-' . $d,
    'class' => $gatewayClass
  ]);

    echo H::openTag('div', $panelHead); //4
    // Permalink to this exact gateway's section — deep-links straight
    // past the tab-switching JS, e.g. for support docs/conversations
    // ("Settings > Online Payment > Stripe") instead of "scroll down
    // to find it".
    echo H::openTag('a', [
     'href' => $tab_index_url . '#gateway-settings-' . $d,
     'class' => 'text-decoration-none text-reset',
    ]);
    echo ucwords(str_replace('_', ' ', $driver));
    echo H::closeTag('a');
    if (isset($gateway_credential_urls[$d]) && $gateway_credential_urls[$d] !== '') {
        echo H::openTag('a', [
         'href' => $gateway_credential_urls[$d],
         'target' => '_blank',
         'rel' => 'noopener noreferrer',
         'class' => 'small ms-2',
        ]);
        echo $translator->translate('online.payment.get.credentials');
        echo H::closeTag('a');
    }
    echo H::openTag('div', $pullRight); //5
    echo H::openTag('div', $noMargin); //6
    $body[$pfxGateway . $d . $sfxEnabled] =
    $s->getSetting('gateway_' . $d . '_enabled');
    echo H::openTag('input', [
     'type' => 'hidden',
     'name' => $pfxGateway . $d . $sfxEnabled,
     'value' => '0'
    ]);
    echo H::openTag('input', [
     'type' => 'checkbox',
     'class' => 'form-check-input',
     'name' => $pfxGateway . $d . $sfxEnabled,
     'value' => '1',
     'id' => $pfxGateway . $d . $sfxEnabled,
     'checked' => ($body[$pfxGateway .
     $d . $sfxEnabled] == '1')
     ? 'checked'
     : null
    ]);
    echo H::openTag('label', ['class' => 'form-check-label', 'for' => $pfxGateway . $d . $sfxEnabled]);
    echo $translator->translate('enabled');
    echo H::closeTag('label');
    echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::closeTag('div'); //4

    echo H::openTag('div', $panelBodySmall); //4

    // GoCardless offers two separate integration models in its dashboard —
    // this only ever uses the simpler one, so make that explicit up front
    // rather than leaving it to be discovered by trial and error.
    if ($d == 'gocardless') {
        echo H::openTag('p', ['class' => 'text-muted small']);
        echo H::openTag('b');
        echo 'No Partner Integration: ';
        echo H::closeTag('b');
        echo 'this uses GoCardless\'s ';
        echo H::openTag('b');
        echo 'Direct integration';
        echo H::closeTag('b');
        echo ' — a single Access Token for your own account (in your GoCardless dashboard: ';
        echo H::openTag('pre');
        echo 'Developers > Create > Access Token';
        echo H::closeTag('pre');
        echo '). Do ';
        echo H::openTag('b');
        echo 'not';
        echo H::closeTag('b');
        echo ' create a Partner app — that OAuth-based integration is only for platforms connecting many separate merchants\' own GoCardless accounts, which this application does not do.';
        echo H::closeTag('p');
        echo H::openTag('p', ['class' => 'text-muted small']);
        echo H::openTag('b');
        echo 'When creating the token: ';
        echo H::closeTag('b');
        echo 'set Scope to ';
        echo H::openTag('b');
        echo 'Read-write access';
        echo H::closeTag('b');
        echo ' — Read only access will not let this application create redirect flows, mandates, or schedule payments. GoCardless only shows the token value once, immediately after creation, so copy it into the field below straight away.';
        echo H::closeTag('p');
        echo H::openTag('p', ['class' => 'text-muted small']);
        echo H::openTag('b');
        echo 'When creating the webhook endpoint: ';
        echo H::closeTag('b');
        echo 'set URL to this site\'s ';
        echo H::openTag('pre');
        echo '/paymentinformation/goCardlessWebhook';
        echo H::closeTag('pre');
        echo 'route (already exempt from CSRF checks), and leave ';
        echo H::openTag('b');
        echo '"Generate a secret for me"';
        echo H::closeTag('b');
        echo ' selected. As with the access token, GoCardless only shows the generated secret once, immediately after clicking Create — copy it into the Webhook Secret field below straight away.';
        echo H::closeTag('p');
    }

    /**
    * @var string $key
    * @var array $setting
    * @var string $setting['label']
    * @var string $setting['password']
    * @var string $setting['type']
    */
    foreach ($fields as $key => $setting) {
        $body[$pfxGateway . $d . '_' . $key . ']'] =
        $s->getSetting('gateway_' . $d . '_' . $key);

        if ($setting['type'] == 'checkbox') {
            echo H::openTag('div', $checkbox); //5
            echo H::openTag('input', [
             'type' => 'hidden',
             'name' => $pfxGateway . $d . '_' .
             $key . ']',
             'value' => '0'
            ]);
            echo H::openTag('input', [
             'type' => 'checkbox',
             'class' => 'form-check-input',
             'id' => $pfxGateway . $d . '_' . $key . ']',
             'name' => $pfxGateway . $d . '_' .
             $key . ']',
             'value' => '1',
             'checked' => ($body[$pfxGateway .
             $d . '_' . $key . ']'] == '1')
             ? 'checked'
             : null
            ]);
            echo H::openTag('label', ['class' => 'form-check-label', 'for' => $pfxGateway . $d . '_' . $key . ']']);
            echo $setting['label'];
            echo H::closeTag('label');
            echo H::closeTag('div'); //5

        } else {
            // 'textarea' is treated identically to 'password' for
            // encryption/decryption purposes below — the only difference is the
            // HTML control rendered (a real <textarea>, not a single-line
            // <input>). Added specifically for TrueLayer's PEM-format signing
            // private key: pasting genuinely multi-line content into a
            // single-line <input type="password"> silently strips/collapses the
            // internal newlines a PEM's structure depends on, corrupting the
            // key without any visible error until the SDK later fails to parse
            // it — confirmed live, 2026-08-16 ("Unable to load the key").
            if ($setting['type'] == 'password' || $setting['type'] == 'textarea') {
                // $body[...] is NOT raw submitted plaintext here — the foreach
                // above (line 200-201) unconditionally overwrites it with the
                // real stored (encrypted) setting value on every render, GET or
                // POST. decode() is therefore correct and necessary. A previous
                // "fix" here removed this call on the wrong assumption that $body
                // held fresh plaintext; it didn't, and that change corrupted every
                // gateway's stored secret by re-encrypting already-encrypted
                // ciphertext on the next save. See
                // docs/CRYPTOR_DECODE_REGRESSION_AUGUST_2026.md.
                //
                // The try/catch below is a separate, deliberate safety net: if a
                // stored value is ever malformed (however that happens — a
                // partial write, manual DB edit, or corruption from the
                // regression above), decode() throws CryptorException and used to
                // crash this entire settings page, for every tab, not just the
                // affected gateway. Failing safe to an empty field is strictly
                // better than a fatal error blocking access to every gateway's
                // settings at once.
                try {
                    $inputValue = (string) (strlen((string)
                     $body[$pfxGateway . $d . '_' .
                     $key . ']']) > 0
                     ? $s->decode((string)
                     $body[$pfxGateway . $d . '_' .
                     $key . ']'])
                     : '');
                } catch (\App\Invoice\Libraries\CryptorException) {
                    $inputValue = '';
                }
            } else {
                $inputValue = (string)
                $body[$pfxGateway . $d . '_' .
                $key . ']'];
            }

            echo H::openTag('div', $formGroup); //6
            echo H::openTag('label', [
            'for' => $pfxGateway . $d . '_' .
            $key . ']'
    ]);
            // Most gateways share the generic online.payment.{key} label
            // ('Secret Key', 'Webhook Secret', ...) — fine when every gateway
            // really does call it that. Where a gateway's own dashboard uses
            // different wording (e.g. Checkout.com's 'Secret API Key'/'Webhook
            // Signature Key'), an online.payment.{driver}.{key} override takes
            // priority. translate() returns the id itself, unchanged, when no
            // message is defined for it — the documented Yii3 way to detect a
            // missing key, used here rather than a separate "has key" lookup.
            $gatewaySpecificLabelKey = 'online.payment.' . $d . '.' . $key;
            $gatewaySpecificLabel = $translator->translate($gatewaySpecificLabelKey);
            echo $gatewaySpecificLabel !== $gatewaySpecificLabelKey
             ? $gatewaySpecificLabel
             : $translator->translate('online.payment.' . $key);
            echo H::closeTag('label');
            // Field-specific link (e.g. Checkout.com's Processing Channel Id,
            // found on its own settings page, not the general credentials
            // page the gateway-title-level link above already points to) —
            // separate from $gateway_credential_urls, which is one link per
            // whole gateway.
            if (isset($gateway_field_urls[$d . '.' . $key]) && $gateway_field_urls[$d . '.' . $key] !== '') {
                echo H::openTag('a', [
                 'href' => $gateway_field_urls[$d . '.' . $key],
                 'target' => '_blank',
                 'rel' => 'noopener noreferrer',
                 'class' => 'small ms-2',
                ]);
                echo $translator->translate('online.payment.find.here');
                echo H::closeTag('a');
            }
            if ($setting['type'] == 'textarea') {
                echo H::textarea(
                    $pfxGateway . $d . '_' . $key . ']',
                    $inputValue,
                    [
       'class' => 'form-control',
       'id' => $pfxGateway . $d . '_' . $key . ']',
       'rows' => 6,
      ],
                );
            } elseif ($setting['type'] == 'password') {
                // Reveal toggle + copy-to-clipboard, both wrapped in a
                // position-relative container so they sit inside the input itself.
                // Wired up generically, not an inline onclick — see
                // feedback_no_raw_script_tags:
                //  - password-reveal-toggle: settings.ts, class + data-target,
                //    resolved via getElementById (not a CSS selector — this field's
                //    id contains literal [ ] characters that would break
                //    querySelector('#' . $id)).
                //  - copy-to-clipboard: data-actions.ts, data-action +
                //    data-copy-target-id, same getElementById reasoning, extended to
                //    read an <input>'s .value (source.textContent is empty for a
                //    real input — the pre-existing copy-to-clipboard in
                //    partial_settings_system_updates.php only ever targeted a <pre>).
                // The copy button is positioned to the left of the (already
                // live-verified, untouched here) reveal-eye button via an explicit
                // inline offset rather than another Bootstrap utility class, so its
                // fixed pixel width doesn't depend on guessing at the eye button's
                // own rendered size.
                $fieldId = $pfxGateway . $d . '_' . $key . ']';
                echo H::openTag('div', ['class' => 'position-relative']);
                echo H::openTag('input', [
                 'type' => $setting['type'],
                 'class' => 'form-control',
                 'style' => 'padding-right: 5rem',
                 'name' => $fieldId,
                 'id' => $fieldId,
                 'value' => $inputValue
                ]);
                echo H::openTag('button', [
                 'type' => 'button',
                 'class' => 'btn btn-link p-1 copy-to-clipboard-toggle',
                 'style' => 'position: absolute; top: 50%; right: 2.75rem; transform: translateY(-50%);',
                 'data-action' => 'copy-to-clipboard',
                 'data-copy-target-id' => $fieldId,
                 'data-copied-label' => $translator->translate('copied'),
                 'aria-label' => $translator->translate('copy.to.clipboard'),
                 'title' => $translator->translate('copy.to.clipboard'),
                 'tabindex' => '-1',
                ]);
                echo H::tag('i', '', ['class' => 'bi bi-clipboard']);
                echo H::closeTag('button');
                echo H::openTag('button', [
                 'type' => 'button',
                 'class' => 'btn btn-link position-absolute top-50 end-0 translate-middle-y password-reveal-toggle',
                 'data-target' => $fieldId,
                 'aria-label' => 'Show password',
                 'tabindex' => '-1',
                ]);
                echo H::tag('i', '', ['class' => 'bi bi-eye']);
                echo H::closeTag('button');
                echo H::closeTag('div');
            } else {
                echo H::openTag('input', [
                 'type' => $setting['type'],
                 'class' => 'form-control',
                 'name' => $pfxGateway . $d . '_' .
                 $key . ']',
                 'id' => $pfxGateway . $d . '_' .
                 $key . ']',
                 'value' => $inputValue
                ]);
            }
            if ($setting['type'] == 'password' || $setting['type'] == 'textarea') {
                echo H::openTag('input', [
                 'type' => 'hidden',
                 'value' => '1',
                 'name' => $pfxGateway . $d . '_' .
                 $key . '_field_is_password]'
                ]);
            }
            // Adyen's HMAC key is shown once, at generation time, and never
            // again — copying it isn't the same action as saving the webhook's
            // own configuration page, an easy mistake that silently leaves
            // Adyen signing with a different (stale) key than whatever's
            // pasted here. This button computes the saved key's own KCV so it
            // can be compared by eye against the KCV Adyen's Customer Area
            // shows for that webhook, catching exactly that mistake without
            // needing a real test payment first. See
            // docs/ADYEN_WEBHOOK_HMAC_KEY_NOT_SAVED_AUGUST_2026.md.
            if ($d == 'adyen' && $key == 'webhookHmacKey') {
                echo H::openTag('a', [
                 'href' => $adyen_hmac_verify_url,
                 'class' => 'btn btn-primary mt-2',
                ]);
                echo '🔑 ' . $translator->translate('online.payment.adyen.hmac.kcv.verify');
                echo H::closeTag('a');
                echo H::openTag('div', ['class' => 'form-text']);
                echo $translator->translate('online.payment.adyen.hmac.kcv.hint');
                echo H::closeTag('div');
            }
            echo H::closeTag('div'); //5

        }
    }

    echo H::openTag('hr');

    echo H::openTag('div', $formGroup); //9
    echo H::openTag('label', [
    'for' => $pfxGateway . $d . $sfxCurrency
       ]);
    echo $translator->translate('currency');
    echo H::closeTag('label');
    $body[$pfxGateway . $d . $sfxCurrency] =
    $s->getSetting('gateway_' . $d . '_currency');
    echo H::openTag('select', [
     'name' => $pfxGateway . $d . $sfxCurrency,
     'id' => $pfxGateway . $d . $sfxCurrency,
     'class' => 'form-select',
    ]);
    /**
    * @var string $val
    */
    foreach (array_keys($gateway_currency_codes) as $val) {
        echo  new Option()
         ->value($val)
         ->selected($body[$pfxGateway .
          $d . $sfxCurrency] == $val)
          ->content($val);
    }
    echo H::closeTag('select');
    echo H::closeTag('div'); //9

    if ($d == 'mollie') {
        echo H::openTag('div', $formGroup); //9
        echo H::openTag('label', [
        'for' => $pfxGateway . $d . $sfxLocale
       ]);
        echo $translator->translate(
            'payment.gateway.default.locale'
        );
        echo H::closeTag('label');
        $body[$pfxGateway . $d . $sfxLocale] =
        $s->getSetting('gateway_' . $d . '_locale');
        $locales = $s->mollieSupportedLocaleArray();
        echo H::openTag('select', [
         'name' => $pfxGateway . $d . $sfxLocale,
         'id' => $pfxGateway . $d . $sfxLocale,
         'class' => 'form-select',
        ]);
        /**
        * @var string $value
        */
        foreach ($locales as $value) {
            echo  new Option()
             ->value($value)
             ->selected($body['settings[gateway_mollie_locale]']
              == $value)
              ->content($value);
        }
        echo H::closeTag('select');
        echo H::closeTag('div'); //9
    }

    echo H::openTag('div', $formGroup); //9
    echo H::openTag('label', [
    'for' => $pfxGateway . $d . $sfxPaymentMethod
       ]);
    echo $translator->translate('online.payment.method');
    echo H::closeTag('label');
    $body[$pfxGateway . $d . $sfxPaymentMethod] =
    $s->getSetting('gateway_' . $d . '_payment_method');
    echo H::openTag('select', [
     'name' => $pfxGateway . $d . $sfxPaymentMethod,
     'id' => $pfxGateway . $d . $sfxPaymentMethod,
     'class' => 'form-select',
    ]);
    /**
     * @var App\Infrastructure\Persistence\PaymentMethod\PaymentMethod $payment_method
     */
    foreach ($payment_methods as $payment_method) {
        echo  new Option()
         ->value($payment_method->reqId())
         ->selected($body[$pfxGateway .
          $d . $sfxPaymentMethod] ==
          $payment_method->reqId())
          ->content($payment_method->getName() ?? '');
    }
    echo H::closeTag('select');
    echo H::closeTag('div'); //9

    echo H::closeTag('div'); //9

    echo H::closeTag('div'); //9
}

echo H::closeTag('div'); //9
echo H::closeTag('div'); //9
