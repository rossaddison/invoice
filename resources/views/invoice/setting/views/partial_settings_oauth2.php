<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;
use Yiisoft\Html\Tag\Option;

/**
* Related logic: see src\Auth\controller\AuthController
* Related logic: see src\Auth\Trait\Oauth2
* Related logic: see src\Auth\Controller\SignupController
* Related logic: see App\Widget\Button
* Related logic: see resources\views\auth\login
* Related logic: see resource\views\signup\signup
* Related logic: see App\Invoice\InvoiceController
*   no_developer_sandbox_hmrc_continue_button
* Related logic: see App\Invoice\Setting\SettingController
*   function tabIndex oauth2
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $openBankingProviders
* @var array $body
*/

$kNoHmrc = 'settings[no_developer_sandbox_hmrc_continue_button]';
$kNoGithub = 'settings[no_github_continue_button]';
$kNoGoogle = 'settings[no_google_continue_button]';
$kNoFacebook = 'settings[no_facebook_continue_button]';
$kNoGovuk = 'settings[no_govuk_continue_button]';
$kNoLinkedin = 'settings[no_linkedin_continue_button]';
$kNoMicrosoft = 'settings[no_microsoftonline_continue_button]';
$kNoOpenBanking = 'settings[no_openbanking_continue_button]';
$kOpenBankingProvider = 'settings[open_banking_provider]';
$kNoOpenIdConnect = 'settings[no_openidconnect_continue_button]';
$kNoVkontakte = 'settings[no_vkontakte_continue_button]';
$kNoX = 'settings[no_x_continue_button]';
$kNoYandex = 'settings[no_yandex_continue_button]';

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-xs-12 col-md-8 col-md-offset-2']); //2
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H::openTag('label');
     echo H::openTag('i', ['class' => 'bi bi-info-circle']);
     echo H::closeTag('i');
     echo $translator->translate('oauth2') . ' ' . '⛔';
    echo H::closeTag('label');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'form-group']); //5

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoHmrc] = $s->getSetting('no_developer_sandbox_hmrc_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoHmrc,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoHmrc,
        'value' => '1',
        'checked' => $body[$kNoHmrc] == 1
       ]);
       echo H::tag('img', '', [
        'src' => '/img/govuk-opengraph-image.png',
        'width' => '12',
        'height' => '12'
       ]);
       echo chr(32) . $translator->translate('gov.developer.sandbox') . chr(32)
       . $translator->translate('gov.developer.sandbox.uk');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoGithub] = $s->getSetting('no_github_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoGithub,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoGithub,
        'value' => '1',
        'checked' => $body[$kNoGithub] == 1
       ]);
       echo  new I()->addClass('bi bi-github')->render() . ' Github';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoGoogle] = $s->getSetting('no_google_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoGoogle,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoGoogle,
        'value' => '1',
        'checked' => $body[$kNoGoogle] == 1
       ]);
       echo  new I()->addClass('bi bi-google')->render() . ' Google';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoFacebook] = $s->getSetting('no_facebook_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoFacebook,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoFacebook,
        'value' => '1',
        'checked' => $body[$kNoFacebook] == 1
       ]);
       echo  new I()->addClass('bi bi-facebook')->render() . ' Facebook';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoGovuk] = $s->getSetting('no_govuk_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoGovuk,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoGovuk,
        'value' => '1',
        'checked' => $body[$kNoGovuk] == 1
       ]);
       echo H::tag('img', '', [
        'src' => '/img/govuk-opengraph-image.png',
        'width' => '12',
        'height' => '12'
       ]);
       echo ' Gov Uk';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoLinkedin] = $s->getSetting('no_linkedin_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoLinkedin,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoLinkedin,
        'value' => '1',
        'checked' => $body[$kNoLinkedin] == 1
       ]);
       echo  new I()->addClass('bi bi-linkedin')->render() . ' LinkedIn';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoMicrosoft] = $s->getSetting('no_microsoftonline_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoMicrosoft,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoMicrosoft,
        'value' => '1',
        'checked' => $body[$kNoMicrosoft] == 1
       ]);
       echo  new I()->addClass('bi bi-microsoft')->render()
       . ' Microsoft Online';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div'); //6
      echo H::openTag('div', ['class' => 'checkbox']); //7
       $body[$kNoOpenBanking] = $s->getSetting('no_openbanking_continue_button');
       echo H::openTag('label');
        echo H::tag('input', '', [
         'type' => 'hidden',
         'name' => $kNoOpenBanking,
         'value' => '0'
        ]);
        echo H::tag('input', '', [
         'type' => 'checkbox',
         'name' => $kNoOpenBanking,
         'value' => '1',
         'checked' => $body[$kNoOpenBanking] == 1
        ]);
        echo H::openTag('svg', [
         'xmlns' => 'http://www.w3.org/2000/svg',
         'width' => '12',
         'height' => '12',
         'viewBox' => '0 0 12 12',
         'fill' => 'none',
         'aria-hidden' => 'true',
         'focusable' => 'false'
        ]);
         echo H::tag('circle', '', [
          'cx' => '6', 'cy' => '6', 'r' => '5',
          'stroke' => '#000', 'stroke-width' => '1.5', 'fill' => '#fff'
         ]);
         echo H::openTag('g');
          echo H::tag('rect', '', [
           'x' => '3', 'y' => '5', 'width' => '6', 'height' => '4',
           'rx' => '0.5', 'fill' => '#fff', 'stroke' => '#000',
           'stroke-width' => '0.6'
          ]);
          echo H::tag('rect', '', [
           'x' => '4.5', 'y' => '6', 'width' => '3', 'height' => '0.6',
           'rx' => '0.3', 'fill' => '#000'
          ]);
          echo H::tag('rect', '', [
           'x' => '4.5', 'y' => '7.2', 'width' => '1.75', 'height' => '0.6',
           'rx' => '0.3', 'fill' => '#000', 'opacity' => '0.7'
          ]);
          echo H::tag('circle', '', [
           'cx' => '6', 'cy' => '3.5', 'r' => '0.9',
           'fill' => '#fff', 'stroke' => '#000', 'stroke-width' => '0.6'
          ]);
          echo H::tag('path', '', [
           'd' => 'M6 4.5V6.5', 'stroke' => '#000',
           'stroke-width' => '0.4', 'stroke-linecap' => 'round'
          ]);
         echo H::closeTag('g');
         echo H::openTag('g');
          echo H::tag('path', '', [
           'd' => 'M2 5.3L6 2.5L10 5.3', 'stroke' => '#000',
           'stroke-width' => '0.6', 'fill' => 'none'
          ]);
         echo H::closeTag('g');
        echo H::closeTag('svg');
        echo ' Open Banking';
       echo H::closeTag('label');
      echo H::closeTag('div'); //7
      echo H::openTag('div'); //7
       $body[$kOpenBankingProvider] = $s->getSetting('open_banking_provider');
       echo H::openTag('select', [
        'name' => $kOpenBankingProvider,
        'id' => $kOpenBankingProvider,
        'class' => 'form-control form-control-lg',
       ]);
        /**
        * @var string $key
        * @var string $value
        */
        foreach ($openBankingProviders as $value) {
        echo  new Option()
         ->value($value)
         ->selected($value == $body[$kOpenBankingProvider])
         ->content(ucfirst($value));
        }
       echo H::closeTag('select');
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoOpenIdConnect] = $s->getSetting('no_openidconnect_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoOpenIdConnect,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoOpenIdConnect,
        'value' => '1',
        'checked' => $body[$kNoOpenIdConnect] == 1
       ]);
       echo  new I()->addClass('bi')->render() . ' Open Id Connect';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoVkontakte] = $s->getSetting('no_vkontakte_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoVkontakte,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoVkontakte,
        'value' => '1',
        'checked' => $body[$kNoVkontakte] == 1
       ]);
       echo H::tag('img', '', [
        'src' => '/img/vkontakte.jpg',
        'width' => '12',
        'height' => '12'
       ]);
       echo ' VKontakte';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoX] = $s->getSetting('no_x_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoX,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoX,
        'value' => '1',
        'checked' => $body[$kNoX] == 1
       ]);
       echo  new I()->addClass('bi bi-twitter')->render() . ' X i.e Twitter';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body[$kNoYandex] = $s->getSetting('no_yandex_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => $kNoYandex,
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => $kNoYandex,
        'value' => '1',
        'checked' => $body[$kNoYandex] == 1
       ]);
       echo H::tag('img', '', [
        'src' => '/img/yandex.jpg',
        'width' => '12',
        'height' => '12'
       ]);
       echo ' Yandex';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
