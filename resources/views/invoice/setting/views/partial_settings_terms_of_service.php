<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\H6;
use Yiisoft\Html\Tag\I;

/**
 * Related logic: see resources\views\invoice\setting\tab_index and
 * resources\views\site\termsofservice.php (the actual public page this
 * tab controls) -- see partial_settings_privacy_policy.php's own
 * docblock for why this is a dedicated tab rather than a checkbox
 * inside partial_settings_front_page.php's generic list. Only
 * setting_key row this app actually has for this page is
 * no_front_terms_of_service_page; the page's own content
 * ($companyName, $arbitrationBody, $arbitrationJurisdiction, etc.)
 * comes from the current Company record, not from Settings -- see
 * CommonViewInjection.php -- so this tab links to Company rather than
 * duplicating those fields here.
 *
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 */

echo H::openTag('div', ['class' => 'row']); //1
 echo H::openTag('div', ['class' => 'col-12 col-md-8 offset-md-2']); //2

  echo H::openTag('div', ['class' => 'card mb-3']); //3
   echo H::openTag('div', ['class' => 'card-header']); //4
    echo new H6()->content($translator->translate('menu.terms.of.service'));
   echo H::closeTag('div'); //4
   echo H::openTag('div', ['class' => 'card-body']); //4
    echo H::openTag('div', ['class' => 'form-check']); //5
     $snftosp = 'settings[no_front_terms_of_service_page]';
     $body[$snftosp] = $s->getSetting('no_front_terms_of_service_page');
     echo H::openTag('input', [
      'type' => 'hidden',
      'name' => $snftosp,
      'value' => '0',
     ]);
     echo H::openTag('input', [
      'type' => 'checkbox',
      'class' => 'form-check-input',
      'id' => 'no_front_terms_of_service_page',
      'name' => $snftosp,
      'value' => '1',
      'checked' => ($body[$snftosp] == 1) ? 'checked' : null,
     ]);
     echo H::openTag('label', ['class' => 'form-check-label', 'for' => 'no_front_terms_of_service_page']);
      echo $translator->translate('setting.hide.from.front.page');
     echo H::closeTag('label');
    echo H::closeTag('div'); //5
    echo H::tag('p',
        H::encode($translator->translate('setting.company.details.note')),
        ['class' => 'text-muted mt-3 mb-1']);
    echo H::tag('p',
        H::encode($translator->translate('setting.arbitration.details.note')),
        ['class' => 'text-muted mb-2']);
    echo H::openTag('div', ['class' => 'd-flex gap-2 flex-wrap']); //5
     echo new A()
         ->href($urlGenerator->generate('company/index'))
         ->addClass('btn btn-outline-secondary btn-sm')
         ->content(
             new I()->addClass('bi bi-building me-1'),
             $translator->translate('company'),
         )
         ->render();
     echo new A()
         ->href($urlGenerator->generate('site/termsofservice'))
         ->addAttributes(['target' => '_blank', 'rel' => 'noopener'])
         ->addClass('btn btn-outline-primary btn-sm')
         ->content(
             new I()->addClass('bi bi-eye me-1'),
             $translator->translate('setting.terms.of.service.preview'),
         )
         ->render();
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3

 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
