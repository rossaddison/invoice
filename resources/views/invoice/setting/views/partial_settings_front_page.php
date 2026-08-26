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

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', [ //2
  'class' => 'col-12 col-md-8 offset-md-2'
 ]);
  echo H::openTag('div', ['class' => 'card']); //3
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo H::openTag('label');
     echo ' ' . $translator->translate('front.page') . ' ' . '⛔';
    echo H::closeTag('label');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::openTag('div', ['class' => 'form-check border-bottom mb-2 pb-2']); //select-all
     echo H::openTag('input', [
      'type' => 'checkbox',
      'class' => 'form-check-input',
      'id' => 'front-page-select-all',
      'data-action' => 'select-all',
      'data-target' => '#front-page-checkboxes',
     ]);
     echo H::openTag('label', ['class' => 'form-check-label fw-bold text-secondary', 'for' => 'front-page-select-all']);
      echo $translator->translate('select.all');
     echo H::closeTag('label');
    echo H::closeTag('div'); //select-all
    echo H::openTag('div', ['id' => 'front-page-checkboxes']); //checkboxes wrapper
    echo H::openTag('div', ['class' => 'mb-3']); //5

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfap = 'settings[no_front_about_page]';
      $body[$snfap] = $s->getSetting('no_front_about_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfap,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_about_page',
       'name' => $snfap,
       'value' => '1',
       'checked' => ($body[$snfap] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_about_page']);
       echo $translator->translate('menu.about');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfacp = 'settings[no_front_accreditations_page]';
      $body[$snfacp] = $s->getSetting('no_front_accreditations_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfacp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_accreditations_page',
       'name' => $snfacp,
       'value' => '1',
       'checked' => ($body[$snfacp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_accreditations_page']);
       echo $translator->translate('menu.accreditations');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfcup = 'settings[no_front_contact_us_page]';
      $body[$snfcup] = $s->getSetting('no_front_contact_us_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfcup,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_contact_us_page',
       'name' => $snfcup,
       'value' => '1',
       'checked' => ($body[$snfcup] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_contact_us_page']);
       echo $translator->translate('menu.contact.us');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      // Gates App\Contact\ContactController::interest() (route /interest)
      // -- the "Request a Trade Quote" form linked from a product's Trade
      // Pricing modal (resources/views/shop/catalog/view.php), not the
      // site/contact page above. Newly wired up 2026-08-26 -- this
      // setting previously had a default value but no checkbox and no
      // route check at all.
      $snfcip = 'settings[no_front_contact_interest_page]';
      $body[$snfcip] = $s->getSetting('no_front_contact_interest_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfcip,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_contact_interest_page',
       'name' => $snfcip,
       'value' => '1',
       'checked' => ($body[$snfcip] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_contact_interest_page']);
       echo $translator->translate('menu.contact.interest');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfgp = 'settings[no_front_gallery_page]';
      $body[$snfgp] = $s->getSetting('no_front_gallery_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfgp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_gallery_page',
       'name' => $snfgp,
       'value' => '1',
       'checked' => ($body[$snfgp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_gallery_page']);
       echo $translator->translate('menu.gallery');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfpp = 'settings[no_front_pricing_page]';
      $body[$snfpp] = $s->getSetting('no_front_pricing_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfpp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_pricing_page',
       'name' => $snfpp,
       'value' => '1',
       'checked' => ($body[$snfpp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_pricing_page']);
       echo $translator->translate('menu.pricing');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snftp = 'settings[no_front_team_page]';
      $body[$snftp] = $s->getSetting('no_front_team_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snftp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_team_page',
       'name' => $snftp,
       'value' => '1',
       'checked' => ($body[$snftp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_team_page']);
       echo $translator->translate('menu.team');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snftep = 'settings[no_front_testimonial_page]';
      $body[$snftep] = $s->getSetting('no_front_testimonial_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snftep,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_testimonial_page',
       'name' => $snftep,
       'value' => '1',
       'checked' => ($body[$snftep] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_testimonial_page']);
       echo $translator->translate('menu.testimonial');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfppp = 'settings[no_front_privacy_policy_page]';
      $body[$snfppp] = $s->getSetting('no_front_privacy_policy_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfppp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_privacy_policy_page',
       'name' => $snfppp,
       'value' => '1',
       'checked' => ($body[$snfppp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_privacy_policy_page']);
       echo $translator->translate('menu.privacy.policy');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snftosp = 'settings[no_front_terms_of_service_page]';
      $body[$snftosp] = $s->getSetting('no_front_terms_of_service_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snftosp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_terms_of_service_page',
       'name' => $snftosp,
       'value' => '1',
       'checked' => ($body[$snftosp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_terms_of_service_page']);
       echo $translator->translate('menu.terms.of.service');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfgsp = 'settings[no_front_gateway_status_page]';
      $body[$snfgsp] = $s->getSetting('no_front_gateway_status_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfgsp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_gateway_status_page',
       'name' => $snfgsp,
       'value' => '1',
       'checked' => ($body[$snfgsp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_gateway_status_page']);
       echo $translator->translate('menu.gateway.status');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfwsp = 'settings[no_front_webshop_page]';
      $body[$snfwsp] = $s->getSetting('no_front_webshop_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfwsp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_webshop_page',
       'name' => $snfwsp,
       'value' => '1',
       'checked' => ($body[$snfwsp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_webshop_page']);
       echo $translator->translate('menu.webshop');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfssp = 'settings[no_front_site_slider_page]';
      $body[$snfssp] = $s->getSetting('no_front_site_slider_page');
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfssp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => 'no_front_site_slider_page',
       'name' => $snfssp,
       'value' => '1',
       'checked' => ($body[$snfssp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_site_slider_page']);
       echo $translator->translate('home');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

    echo H::closeTag('div'); //5
    echo H::closeTag('div'); //checkboxes wrapper
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
