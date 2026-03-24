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
      $body['settings[no_developer_sandbox_hmrc_continue_button]'] = 
      $s->getSetting('no_developer_sandbox_hmrc_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden', 
        'name' => 'settings[no_developer_sandbox_hmrc_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_developer_sandbox_hmrc_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_developer_sandbox_hmrc_continue_button]'] 
        == 1
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
      $body['settings[no_github_continue_button]'] = 
      $s->getSetting('no_github_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_github_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_github_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_github_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-github')->render() . ' Github';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_google_continue_button]'] = 
      $s->getSetting('no_google_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_google_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_google_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_google_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-google')->render() . ' Google';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_facebook_continue_button]'] = 
      $s->getSetting('no_facebook_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_facebook_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_facebook_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_facebook_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-facebook')->render() . ' Facebook';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_govuk_continue_button]'] = 
      $s->getSetting('no_govuk_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_govuk_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_govuk_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_govuk_continue_button]'] == 1
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
      $body['settings[no_linkedin_continue_button]'] = 
      $s->getSetting('no_linkedin_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_linkedin_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_linkedin_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_linkedin_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-linkedin')->render() . ' LinkedIn';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_microsoftonline_continue_button]'] = 
      $s->getSetting('no_microsoftonline_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_microsoftonline_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_microsoftonline_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_microsoftonline_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-microsoft')->render() 
       . ' Microsoft Online';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div'); //6
      echo H::openTag('div', ['class' => 'checkbox']); //7
       $body['settings[no_openbanking_continue_button]'] = 
       $s->getSetting('no_openbanking_continue_button');
       echo H::openTag('label');
        echo H::tag('input', '', [
         'type' => 'hidden',
         'name' => 'settings[no_openbanking_continue_button]',
         'value' => '0'
        ]);
        echo H::tag('input', '', [
         'type' => 'checkbox',
         'name' => 'settings[no_openbanking_continue_button]',
         'value' => '1',
         'checked' => $body['settings[no_openbanking_continue_button]'] == 1
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
       $body['settings[open_banking_provider]'] = 
       $s->getSetting('open_banking_provider');
       echo H::openTag('select', [
        'name' => 'settings[open_banking_provider]',
        'id' => 'settings[open_banking_provider]',
        'class' => 'form-control'
       ]);
        /**
        * @var string $key
        * @var string $value
        */
        foreach ($openBankingProviders as $key => $value) {
        echo  new Option()
         ->value($value)
         ->selected($value 
          == $body['settings[open_banking_provider]'])
          ->content(H::encode(ucfirst($value)));
          }
          echo H::closeTag('select');
          echo H::closeTag('div'); //11
          echo H::closeTag('div'); //11
          echo H::openTag('div', ['class' => 'checkbox']); //11
          $body['settings[no_openidconnect_continue_button]'] = 
          $s->getSetting('no_openidconnect_continue_button');
          echo H::openTag('label');
          echo H::tag('input', '', [
          'type' => 'hidden',
          'name' => 'settings[no_openidconnect_continue_button]',
          'value' => '0'
         ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_openidconnect_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_openidconnect_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi')->render() . ' Open Id Connect';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_vkontakte_continue_button]'] = 
      $s->getSetting('no_vkontakte_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_vkontakte_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_vkontakte_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_vkontakte_continue_button]'] == 1
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
      $body['settings[no_x_continue_button]'] = 
      $s->getSetting('no_x_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_x_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_x_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_x_continue_button]'] == 1
       ]);
       echo  new I()->addClass('bi bi-twitter')->render() . ' X i.e Twitter';
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_yandex_continue_button]'] = 
      $s->getSetting('no_yandex_continue_button');
      echo H::openTag('label');
       echo H::tag('input', '', [
        'type' => 'hidden',
        'name' => 'settings[no_yandex_continue_button]',
        'value' => '0'
       ]);
       echo H::tag('input', '', [
        'type' => 'checkbox',
        'name' => 'settings[no_yandex_continue_button]',
        'value' => '1',
        'checked' => $body['settings[no_yandex_continue_button]'] == 1
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
