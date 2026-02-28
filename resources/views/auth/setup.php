<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\{
    FormModel\Field as F,
    Html\Html as H,
    Html\Tag\A,
    Html\Tag\Form,
    Html\Tag\Img
};

/**
 * @var string $csrf
 * @var array $class
 * @var string|null $error
 * @var string $qrDataUri
 * @var string $totpSecret
 * @var App\Auth\Form\TwoFactorAuthenticationSetupForm $formModel
 * @var App\Invoice\Setting\SettingRepository $s
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

$tfa = 'two.factor.authentication';

// 2FA Setup View: Show QR code, secret, and input for code

echo H::openTag('div', ['class' => (string) $class[1]]);
 echo H::openTag('div', ['class' => (string) $class[2]]);
  echo H::openTag('div', ['class' => (string) $class[3]]);
   echo H::openTag('div', ['class' => (string) $class[4]]);
    echo H::openTag('div', ['class' => (string) $class[5]]);
     echo H::openTag('h5', ['class' => (string) $class[6]]);
      echo $translator->translate($tfa . '.setup');
     echo H::closeTag('h5');
    echo H::closeTag('div');
   echo H::openTag('div', ['class' => (string) $class[17]]);
    echo H::openTag('p');
     echo $translator->translate($tfa . '.scan');
     echo A::tag()
          ->href('https://getaegis.app')
          ->addAttributes(['target' => '_blank'])   
          ->content(Img::tag()
                    ->size(60, 60)
                    ->src('/img/aegis.png')
                    ->alt('Opensource Two Factor Authentication Software'))
          ->render();
    echo H::closeTag('p');
    echo Img::tag()
    ->width(160)
    ->height(240)
    ->src(H::encode($qrDataUri))
    ->alt("2FA QR Code")
    ->render();
    echo H::openTag('p');
     echo $translator->translate($tfa . '.qr.code.enter.manually');
    echo H::closeTag('p');
   echo H::closeTag('div');
   echo H::openTag('div', [
       'class' => $class[17],
       'style' => 'max-width:400px;']);
    echo H::openTag('div', ['class' => 'input-group']);
     echo H::input('password', 'secret', H::encode($totpSecret), [
        'class' => 'form-control',
        'id' => 'secretInput',
        'readonly' => true,
     ]);
     echo Button::tfaToggleSecret();
     echo Button::tfaCopyToClipboard();
    echo H::closeTag('div');
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => $class[10]]);
    echo Form::tag()
     ->post($urlGenerator->generate('auth/verifySetup'))
     ->class('form-floating')
     ->csrf($csrf)
     ->id('twoFactorAuthenticationSetupForm')
     ->open();
     echo F::text($formModel, 'code')
      ->addInputAttributes([
          'autocomplete' => 'current-code',
          'id' => 'code',
          'name' => 'code',
          'minlength' => 6,
          // Only the otp is entered here (6 digit).
          // Not the recovery code (8 digit).
          'maxlength' => 6,
          'type' => 'tel',
      ])
      ->error($error ?? '')
      ->required(true)
      ->inputClass('form-control')
      ->label($translator->translate('layout.password.otp.6.first'))
      ->autofocus();
     echo F::submitButton()
      ->buttonId('code-button')
      ->buttonClass('btn btn-primary')
      ->name('code-button')
      ->content($translator->translate('layout.submit'));
    echo Form::tag()->close();
   echo H::closeTag('div');
   echo H::openTag('div', ['class' => $class[17]]);
     for ($i = 1; $i <= 9; $i++) {
      echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-info btn-sm btn-digit',
       'data-digit' => $i]);
          echo $i;
      echo H::closeTag('button');
      echo ' ';
     };
     echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-info btn-sm btn-digit',
       'data-digit' => '0']);
      echo 0;
     echo H::closeTag('button');
     echo ' ';
     echo H::openTag('button', [
       'type' => 'button',
       'class' => 'btn btn-info btn-sm btn-clear-otp']);
      echo 'Clear';
     echo H::closeTag('button');
   echo H::closeTag('div'); // 4
  echo H::closeTag('div'); // 3
 echo H::closeTag('div'); // 2
echo H::closeTag('div'); // 1