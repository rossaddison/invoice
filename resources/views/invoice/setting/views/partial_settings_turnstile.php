<?php

declare(strict_types=1);

use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Input;

/**
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var array $body
 */

$row       = ['class' => 'row'];
$colMd6    = ['class' => 'col-12 col-md-6'];
$colMd8    = ['class' => 'col-12 col-md-8 offset-md-2'];
$panel     = ['class' => 'card'];
$panelHead = ['class' => 'card-header'];
$panelBody = ['class' => 'card-body'];
$formGroup = ['class' => 'mb-3'];

echo H::openTag('div', $row); //1
 echo H::openTag('div', $colMd8); //2
  echo H::openTag('div', $panel); //3
   echo H::openTag('div', $panelHead); //4
    echo 'Cloudflare Turnstile — Bot Protection';
   echo H::closeTag('div'); //4
   echo H::openTag('div', $panelBody); //4
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('p');
        echo H::openTag('b');
         echo '1. ';
        echo H::closeTag('b');
        echo 'Create a Turnstile widget on the ';
        echo H::openTag('a', [
         'href'   => 'https://dash.cloudflare.com/?to=/:account/turnstile',
         'target' => '_blank',
        ]);
         echo 'Cloudflare Turnstile dashboard';
        echo H::closeTag('a');
        echo ' and copy the Site Key and Secret Key into the fields below.';
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '2. ';
        echo H::closeTag('b');
        echo 'Leave both fields blank (or set the secret to ';
        echo H::openTag('code');
         echo '0';
        echo H::closeTag('code');
        echo ') to disable verification on localhost / development.';
       echo H::closeTag('p');
       echo H::openTag('p');
        echo H::openTag('b');
         echo '3. ';
        echo H::closeTag('b');
        echo 'The ';
        echo H::openTag('code');
         echo 'cf-turnstile-response';
        echo H::closeTag('code');
        echo ' token is verified server-side against ';
        echo H::openTag('code');
         echo 'challenges.cloudflare.com/turnstile/v0/siteverify';
        echo H::closeTag('code');
        echo ' before any account is created.';
       echo H::closeTag('p');
       echo H::openTag('label', ['for' => 'settings[turnstile_site_key]']);
        echo 'Site Key';
        echo $s->infoIcon('turnstile_site_key');
       echo H::closeTag('label');
       $siteKey = 'settings[turnstile_site_key]';
       $body[$siteKey] = $s->getSetting('turnstile_site_key');
       echo new Input()
            ->type('text')
            ->class('form-control form-control-lg')
            ->id($siteKey)
            ->name($siteKey)
            ->value(H::encode($body[$siteKey]));
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
    echo H::openTag('div', $row); //5
     echo H::openTag('div', $colMd6); //6
      echo H::openTag('div', $formGroup); //7
       echo H::openTag('label', ['for' => 'settings[turnstile_secret_key]']);
        echo 'Secret Key';
        echo $s->infoIcon('turnstile_secret_key');
       echo H::closeTag('label');
       $secretKey = 'settings[turnstile_secret_key]';
       $body[$secretKey] = $s->getSetting('turnstile_secret_key');
       echo new Input()
            ->type('password')
            ->class('form-control form-control-lg')
            ->id($secretKey)
            ->name($secretKey)
            ->value(H::encode($body[$secretKey]));
      echo H::closeTag('div'); //7
     echo H::closeTag('div'); //6
    echo H::closeTag('div'); //5
   echo H::closeTag('div'); //4
  echo H::closeTag('div'); //3
 echo H::closeTag('div'); //2
echo H::closeTag('div'); //1
