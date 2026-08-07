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
* @var array $gateway_regions
* @var array $payment_methods
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
$sfxRegion = '_region]';
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
    echo ucwords(str_replace('_', ' ', $driver));
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
    // Password fields never need decode() here: $body is this request's
    // own freshly-submitted plaintext (what the browser just posted),
    // never previously-encrypted data — on a fresh GET, $body is empty
    // (the field starts blank), so decode() would only ever be reached
    // on an actual submission, where it's always raw plaintext the user
    // just typed. Calling decode() on it is a category error, not a
    // meaningful round-trip — it either coincidentally happens to be
    // valid ciphertext-shaped input (rare, wrong result) or throws
    // CryptorException, exactly as reported. Storage-side encoding
    // still happens correctly and separately in saveOneSetting().
    $inputValue = (string)
     $body[$pfxGateway . $d . '_' .
     $key . ']'];

     echo H::openTag('div', $formGroup); //6
     echo H::openTag('label', [
     'for' => $pfxGateway . $d . '_' .
     $key . ']'
    ]);
      echo $translator->translate(
       'online.payment.' . $key
      );
     echo H::closeTag('label');
     echo H::openTag('input', [
      'type' => $setting['type'],
      'class' => 'form-select',
      'name' => $pfxGateway . $d . '_' .
      $key . ']',
      'id' => $pfxGateway . $d . '_' .
      $key . ']',
      'value' => $inputValue
     ]);
     if ($setting['type'] == 'password') {
     echo H::openTag('input', [
      'type' => 'hidden',
      'value' => '1',
      'name' => $pfxGateway . $d . '_' .
      $key . '_field_is_password]'
     ]);
     }
    echo H::closeTag('div'); //5

    }
    }

    echo H::openTag('hr');

    // regions are specific to Amazon Pay
    if ($d == 'amazon_pay') {
    echo H::openTag('div', $formGroup); //5
     echo H::openTag('label', [
      'for' => $pfxGateway . $d . $sfxRegion
     ]);
      echo $translator->translate('online.payment.region');
     echo H::closeTag('label');
     $body[$pfxGateway . $d . $sfxRegion] =
     $s->getSetting('gateway_' . $d . '_region');
     echo H::openTag('select', [
      'name' => $pfxGateway . $d . $sfxRegion,
      'id' => $pfxGateway . $d . $sfxRegion,
      'class' => 'form-select',
     ]);
      /**
      * @var string $val
      */
      foreach (array_keys($gateway_regions) as $val) {
      echo  new Option()
       ->value($val)
       ->selected($body[$pfxGateway .
        $d . $sfxRegion] == $val)
        ->content($val);
        }
        echo H::closeTag('select');
        echo H::closeTag('div'); //9
        }

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
