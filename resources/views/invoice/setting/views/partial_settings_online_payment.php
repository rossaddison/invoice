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
$colMd8 = ['class' => 'col-xs-12 col-md-8 col-md-offset-2'];
$panel = ['class' => 'panel panel-default'];
$panelHead = ['class' => 'panel-heading'];
$panelBody = ['class' => 'panel-body'];
$formGroup = ['class' => 'form-group'];
$formControl = ['class' => 'form-control'];
$checkbox = ['class' => 'checkbox'];
$pullRight = ['class' => 'pull-right'];
$noMargin = ['class' => 'checkbox no-margin'];
$panelBodySmall = ['class' => 'panel-body small'];
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
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[enable_online_payments]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[enable_online_payments]',
        'value' => '1',
        'checked' => ($body['settings[enable_online_payments]']
        == '1') ? 'checked' : null
       ]);
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
      'class' => 'form-control'
     ]);
      echo (new Option())
       ->value('')
       ->content($translator->translate('none'));
      /**
      * @var string $driver
      * @var array $fields
      */
      foreach ($gateway_drivers as $driver => $fields) {
      $d = strtolower($driver);
      echo (new Option())
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
  foreach ($gateway_drivers as $driver => $fields) :
  $d = strtolower($driver);
  $gatewayClass = 'gateway-settings panel panel-default ' . 
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
      echo H::openTag('label');
       $body['settings[gateway_' . $d . '_enabled]'] = 
       $s->getSetting('gateway_' . $d . '_enabled');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[gateway_' . $d . '_enabled]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[gateway_' . $d . '_enabled]',
        'value' => '1',
        'id' => 'settings[gateway_' . $d . '_enabled]',
        'checked' => ($body['settings[gateway_' . 
        $d . '_enabled]'] == '1') 
        ? 'checked' 
        : null
       ]);
       echo $translator->translate('enabled');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4

   echo H::openTag('div', $panelBodySmall); //4

    /**
    * @var string $key
    * @var array $setting
    * @var string $setting['label']
    * @var string $setting['password']
    * @var string $setting['type']
    */
    foreach ($fields as $key => $setting) {
    $body['settings[gateway_' . $d . '_' . $key . ']'] = 
    $s->getSetting('gateway_' . $d . '_' . $key);

    if ($setting['type'] == 'checkbox') :

    echo H::openTag('div', $checkbox); //5
     echo H::openTag('label');                                    
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => 'settings[gateway_' . $d . '_' . 
       $key . ']',
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'name' => 'settings[gateway_' . $d . '_' . 
       $key . ']',
       'value' => '1',
       'checked' => ($body['settings[gateway_' . 
       $d . '_' . $key . ']'] == '1') 
       ? 'checked' 
       : null
      ]);
      echo $setting['label'];
     echo H::closeTag('label');
    echo H::closeTag('div'); //5

    else : 
    $inputValue = '';
    if ($setting['type'] == 'password') {
    $inputValue = (string) (strlen((string) 
     $body['settings[gateway_' . $d . '_' . 
     $key . ']']) > 0
     ? $s->decode((string) 
     $body['settings[gateway_' . $d . '_' . 
     $key . ']'])
     : '');
     } else {
     $inputValue = (string) 
     $body['settings[gateway_' . $d . '_' . 
     $key . ']'];
     }

     echo H::openTag('div', $formGroup); //6
     echo H::openTag('label', [
     'for' => 'settings[gateway_' . $d . '_' . 
     $key . ']'
    ]);
      echo $translator->translate(
       'online.payment.' . $key
      );
     echo H::closeTag('label');
     echo H::openTag('input', [
      'type' => $setting['type'],
      'class' => 'form-control',
      'name' => 'settings[gateway_' . $d . '_' . 
      $key . ']',
      'id' => 'settings[gateway_' . $d . '_' . 
      $key . ']',
      'value' => $inputValue
     ]);
     if ($setting['type'] == 'password') :
     echo H::openTag('input', [
      'type' => 'hidden',
      'value' => '1',
      'name' => 'settings[gateway_' . $d . '_' . 
      $key . '_field_is_password]'
     ]);
     endif;
    echo H::closeTag('div'); //5

    endif;
    }

    echo H::openTag('hr');

    // regions are specific to Amazon Pay
    if ($d == 'amazon_pay') {
    echo H::openTag('div', $formGroup); //5
     echo H::openTag('label', [
      'for' => 'settings[gateway_' . $d . '_region]'
     ]);
      echo $translator->translate('online.payment.region');
     echo H::closeTag('label');
     $body['settings[gateway_' . $d . '_region]'] = 
     $s->getSetting('gateway_' . $d . '_region');
     echo H::openTag('select', [
      'name' => 'settings[gateway_' . $d . '_region]',
      'id' => 'settings[gateway_' . $d . '_region]',
      'class' => 'form-control'
     ]);
      /**
      * @var string $val
      * @var string $key
      */
      foreach ($gateway_regions as $val => $key) {
      echo (new Option())
       ->value($val)
       ->selected($body['settings[gateway_' . 
        $d . '_region]'] == $val)
        ->content($val);
        }
        echo H::closeTag('select');
        echo H::closeTag('div'); //9
        }

        echo H::openTag('div', $formGroup); //9
        echo H::openTag('label', [
        'for' => 'settings[gateway_' . $d . '_currency]'
       ]);
      echo $translator->translate('currency');
     echo H::closeTag('label');
     $body['settings[gateway_' . $d . '_currency]'] = 
     $s->getSetting('gateway_' . $d . '_currency');
     echo H::openTag('select', [
      'name' => 'settings[gateway_' . $d . '_currency]',
      'id' => 'settings[gateway_' . $d . '_currency]',
      'class' => 'form-control'
     ]);
      /**
      * @var string $val
      * @var string $key
      */
      foreach ($gateway_currency_codes as $val => $key) {
      echo (new Option())
       ->value($val)
       ->selected($body['settings[gateway_' . 
        $d . '_currency]'] == $val)
        ->content($val);
        }
        echo H::closeTag('select');
        echo H::closeTag('div'); //9

        if ($d == 'mollie') {
        echo H::openTag('div', $formGroup); //9
        echo H::openTag('label', [
        'for' => 'settings[gateway_' . $d . '_locale]'
       ]);
      echo $translator->translate(
       'payment.gateway.default.locale'
      );
     echo H::closeTag('label');
     $body['settings[gateway_' . $d . '_locale]'] = 
     $s->getSetting('gateway_' . $d . '_locale');
     $locales = $s->mollieSupportedLocaleArray();
     echo H::openTag('select', [
      'name' => 'settings[gateway_' . $d . '_locale]',
      'id' => 'settings[gateway_' . $d . '_locale]',
      'class' => 'form-control'
     ]);
      /**
      * @var array $locales
      * @var string $key
      * @var string $value
      */
      foreach ($locales as $key => $value) {
      echo (new Option())
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
        'for' => 'settings[gateway_' . $d . '_payment_method]'
       ]);
      echo $translator->translate('online.payment.method');
     echo H::closeTag('label');
     $body['settings[gateway_' . $d . '_payment_method]'] = 
     $s->getSetting('gateway_' . $d . '_payment_method');
     echo H::openTag('select', [
      'name' => 'settings[gateway_' . $d . '_payment_method]',
      'id' => 'settings[gateway_' . $d . '_payment_method]',
      'class' => 'form-control'
     ]);
      /**
      * @var App\Invoice\Entity\PaymentMethod 
      *      $payment_method
      */
      foreach ($payment_methods as $payment_method) {
      echo (new Option())
       ->value($payment_method->getId())
       ->selected($body['settings[gateway_' . 
        $d . '_payment_method]'] == 
        $payment_method->getId())
        ->content($payment_method->getName() ?? '');
        }
        echo H::closeTag('select');
        echo H::closeTag('div'); //9

        echo H::closeTag('div'); //9

        echo H::closeTag('div'); //9
        endforeach;

        echo H::closeTag('div'); //9
        echo H::closeTag('div'); //9
