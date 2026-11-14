<?php

declare(strict_types=1);

use App\Invoice\AppConstants;
use App\Invoice\Enum\ServiceType;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\I;

/**
* Related logic: see resources\views\invoice\setting\tab_index
* @var App\Invoice\Setting\SettingRepository $s
* @var Yiisoft\Translator\TranslatorInterface $translator
* @var \Yiisoft\Router\UrlGeneratorInterface $urlGenerator
* @var array $body
* @var list<ServiceType> $services_offered The services ticked in the services_offered setting
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
      $snfgsp = 'settings[' . AppConstants::SETTING_NO_FRONT_GATEWAY_STATUS_PAGE . ']';
      $body[$snfgsp] = $s->getSetting(AppConstants::SETTING_NO_FRONT_GATEWAY_STATUS_PAGE);
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfgsp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => AppConstants::SETTING_NO_FRONT_GATEWAY_STATUS_PAGE,
       'name' => $snfgsp,
       'value' => '1',
       'checked' => ($body[$snfgsp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => AppConstants::SETTING_NO_FRONT_GATEWAY_STATUS_PAGE]);
       echo $translator->translate('menu.gateway.status');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfpsp = 'settings[' . AppConstants::SETTING_NO_FRONT_PEPPOL_STATUS_PAGE . ']';
      $body[$snfpsp] = $s->getSetting(AppConstants::SETTING_NO_FRONT_PEPPOL_STATUS_PAGE);
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfpsp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => AppConstants::SETTING_NO_FRONT_PEPPOL_STATUS_PAGE,
       'name' => $snfpsp,
       'value' => '1',
       'checked' => ($body[$snfpsp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => AppConstants::SETTING_NO_FRONT_PEPPOL_STATUS_PAGE]);
       echo $translator->translate('menu.peppol.status');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfhasp = 'settings[' . AppConstants::SETTING_NO_FRONT_HMRC_API_STATUS_PAGE . ']';
      $body[$snfhasp] = $s->getSetting(AppConstants::SETTING_NO_FRONT_HMRC_API_STATUS_PAGE);
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfhasp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => AppConstants::SETTING_NO_FRONT_HMRC_API_STATUS_PAGE,
       'name' => $snfhasp,
       'value' => '1',
       'checked' => ($body[$snfhasp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', [
       'class' => 'form-check-label',
       'for' => AppConstants::SETTING_NO_FRONT_HMRC_API_STATUS_PAGE,
      ]);
       echo $translator->translate('menu.hmrc.api.status');
      echo H::closeTag('label');
     echo H::closeTag('div'); //6

     echo H::openTag('div', ['class' => 'form-check']); //6
      $snfwsp = 'settings[' . AppConstants::SETTING_NO_FRONT_WEBSHOP_PAGE . ']';
      $body[$snfwsp] = $s->getSetting(AppConstants::SETTING_NO_FRONT_WEBSHOP_PAGE);
      echo H::openTag('input', [
       'type' => 'hidden',
       'name' => $snfwsp,
       'value' => '0'
      ]);
      echo H::openTag('input', [
       'type' => 'checkbox',
       'class' => 'form-check-input',
       'id' => AppConstants::SETTING_NO_FRONT_WEBSHOP_PAGE,
       'name' => $snfwsp,
       'value' => '1',
       'checked' => ($body[$snfwsp] == 1) ? 'checked' : null
      ]);
      echo H::openTag('label', ['class' => 'form-check-label', 'for' => AppConstants::SETTING_NO_FRONT_WEBSHOP_PAGE]);
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
    echo H::openTag('div', ['class' => 'mt-3 border-top pt-3']); //5
     // The services the business offers: saved by Save, and used by the setup button below. The
     // hidden field makes unticking everything still submit the key, so it can be cleared.
     echo H::tag('h6', $translator->translate('services.offered'), ['class' => 'mb-1']);
     echo $s->infoIcon(ServiceType::SETTING_KEY);
     echo H::openTag('input', [
      'type' => 'hidden',
      'name' => 'settings[' . ServiceType::SETTING_KEY . '][]',
      'value' => '',
     ]);
     foreach (ServiceType::cases() as $service) {
      $inputId = 'service-' . $service->value;
      echo H::openTag('div', ['class' => 'form-check']); //6
       echo H::openTag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
        'id' => $inputId,
        'name' => 'settings[' . ServiceType::SETTING_KEY . '][]',
        'value' => $service->value,
        'checked' => in_array($service, $services_offered, true) ? 'checked' : null,
       ]);
       echo H::openTag('label', ['class' => 'form-check-label', 'for' => $inputId]);
        echo $translator->translate($service->translationKey());
       echo H::closeTag('label');
      echo H::closeTag('div'); //6
     }
    echo H::closeTag('div'); //5
    // Submits this same settings form to the preset route (formaction), so no form is nested inside it
    echo H::openTag('div', ['class' => 'mt-3']); //5
     echo H::tag('button', $translator->translate('window.cleaning.preset'), [
      'type' => 'submit',
      'formaction' => $urlGenerator->generate('setting/windowCleaningPreset'),
      'formmethod' => 'post',
      // the settings form has required fields in tabs that are hidden: without this the browser
      // blocks the submit silently ("An invalid form control is not focusable")
      'formnovalidate' => true,
      'class' => 'btn btn-outline-primary',
      'data-bs-toggle' => 'tooltip',
      'title' => $translator->translate('window.cleaning.preset.tip'),
     ])->render();
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
