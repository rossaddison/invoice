<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;

/**
* Related logic: see resources\views\invoice\setting\tab_index
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $body
*/

echo H::tag('style', ' label { font-weight: bold; } ');
echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H::openTag('label');
     echo  new I()->addClass('bi bi-info-circle')->render();
     echo ' ' . $translator->translate('front.page') . ' ' . '⛔';
    echo H::closeTag('label');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'checkbox border-bottom mb-2 pb-2']); //select-all
     echo H::openTag('label', ['class' => 'fw-bold text-secondary']);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'id' => 'front-page-select-all',
       'onchange' => "document.querySelectorAll('#front-page-checkboxes input[type=checkbox]').forEach(function(cb){ cb.checked = this.checked; cb.dispatchEvent(new Event('change')); }.bind(this))",
      ]);
      echo ' ' . $translator->translate('select.all');
     echo H::closeTag('label');
    echo H::closeTag('div'); //select-all
    echo H::openTag('div', ['id' => 'front-page-checkboxes']); //checkboxes wrapper
    echo H::openTag('div', ['class' => 'form-group']); //5

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfap = 'settings[no_front_about_page]';
      $body[$snfap] = $s->getSetting('no_front_about_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfap,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfap,
        'value' => '1',
        'checked' => ($body[$snfap] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.about');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfacp = 'settings[no_front_accreditations_page]';
      $body[$snfacp] = $s->getSetting('no_front_accreditations_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfacp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfacp,
        'value' => '1',
        'checked' => ($body[$snfacp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.accreditations');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfcdp = 'settings[no_front_contact_details_page]';
      $body[$snfcdp] = $s->getSetting('no_front_contact_details_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfcdp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfcdp,
        'value' => '1',
        'checked' => ($body[$snfcdp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.contact.details');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfcup = 'settings[no_front_contact_us_page]';
      $body[$snfcup] = $s->getSetting('no_front_contact_us_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfcup,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfcup,
        'value' => '1',
        'checked' => ($body[$snfcup] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.contact.us');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfgp = 'settings[no_front_gallery_page]';
      $body[$snfgp] = $s->getSetting('no_front_gallery_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfgp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfgp,
        'value' => '1',
        'checked' => ($body[$snfgp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.gallery');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfpp = 'settings[no_front_pricing_page]';
      $body[$snfpp] = $s->getSetting('no_front_pricing_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfpp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfpp,
        'value' => '1',
        'checked' => ($body[$snfpp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.pricing');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snftp = 'settings[no_front_team_page]';
      $body[$snftp] = $s->getSetting('no_front_team_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snftp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snftp,
        'value' => '1',
        'checked' => ($body[$snftp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.team');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snftep = 'settings[no_front_testimonial_page]';
      $body[$snftep] = $s->getSetting('no_front_testimonial_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snftep,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snftep,
        'value' => '1',
        'checked' => ($body[$snftep] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.testimonial');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfppp = 'settings[no_front_privacy_policy_page]';
      $body[$snfppp] = $s->getSetting('no_front_privacy_policy_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfppp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfppp,
        'value' => '1',
        'checked' => ($body[$snfppp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.privacy.policy');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snftosp = 'settings[no_front_terms_of_service_page]';
      $body[$snftosp] = $s->getSetting('no_front_terms_of_service_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snftosp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snftosp,
        'value' => '1',
        'checked' => ($body[$snftosp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('menu.terms.of.service');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'checkbox']); //6
      $snfssp = 'settings[no_front_site_slider_page]';
      $body[$snfssp] = $s->getSetting('no_front_site_slider_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => $snfssp,
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => $snfssp,
        'value' => '1',
        'checked' => ($body[$snfssp] == 1) ? 'checked' : null
       ]);
       echo $translator->translate('home');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

    echo H::closeTag('div'); //5
    echo H::closeTag('div'); //checkboxes wrapper
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
