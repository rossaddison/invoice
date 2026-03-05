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
  'class' => 'col-xs-12 col-md-8 col-md-offset-2'
 ]);
  echo H::openTag('div', ['class' => 'panel panel-default']); //3
   echo H::openTag('div', ['class' => 'panel-heading']); //4
    echo H::openTag('label');
     echo I::tag()->addClass('bi bi-info-circle')->render();
     echo ' ' . $translator->translate('front.page') . ' ' . '⛔';
    echo H::closeTag('label');
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'panel-body']); //4
    echo H::openTag('div', ['class' => 'form-group']); //5
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_about_page]'] =
      $s->getSetting('no_front_about_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_about_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_about_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_about_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.about');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_accreditations_page]'] =
      $s->getSetting('no_front_accreditations_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_accreditations_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_accreditations_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_accreditations_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.accreditations');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_contact_details_page]'] =
      $s->getSetting('no_front_contact_details_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_contact_details_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_contact_details_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_contact_details_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.contact.details');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_contact_us_page]'] =
      $s->getSetting('no_front_contact_us_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_contact_us_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_contact_us_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_contact_us_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.contact.us');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_gallery_page]'] =
      $s->getSetting('no_front_gallery_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_gallery_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_gallery_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_gallery_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.gallery');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_pricing_page]'] =
      $s->getSetting('no_front_pricing_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_pricing_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_pricing_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_pricing_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.pricing');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_team_page]'] =
      $s->getSetting('no_front_team_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_team_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_team_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_team_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.team');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_testimonial_page]'] =
      $s->getSetting('no_front_testimonial_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_testimonial_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_testimonial_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_testimonial_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.testimonial');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_privacy_policy_page]'] =
      $s->getSetting('no_front_privacy_policy_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_privacy_policy_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_privacy_policy_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_privacy_policy_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.privacy.policy');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_terms_of_service_page]'] =
      $s->getSetting('no_front_terms_of_service_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_terms_of_service_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_terms_of_service_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_terms_of_service_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('menu.terms.of.service');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
     echo H::openTag('div', ['class' => 'checkbox']); //6
      $body['settings[no_front_site_slider_page]'] =
      $s->getSetting('no_front_site_slider_page');
      echo H::openTag('label');
       echo H::openTag('input', [
        'type' => 'hidden',
        'name' => 'settings[no_front_site_slider_page]',
        'value' => '0'
       ]);
       echo H::openTag('input', [
        'type' => 'checkbox',
        'name' => 'settings[no_front_site_slider_page]',
        'value' => '1',
        'checked' =>
        ($body['settings[no_front_site_slider_page]'] == 1) ?
        'checked' : null
       ]);
       echo $translator->translate('home');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
