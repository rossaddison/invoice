<?php

declare(strict_types=1);

use App\Auth\Form\SignupForm;
use App\Widget\Button;
use Yiisoft\{FormModel\Field as F, Html\Html as H, Html\Tag\Form,
    Router\UrlGeneratorInterface, Translator\TranslatorInterface,
    View\WebView, Yii\AuthClient\Widget\AuthChoice};

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var SignupForm                              $formModel
 * @var Yiisoft\Router\CurrentRoute             $currentRoute
 * @var WebView                                 $this
 * @var TranslatorInterface                     $translator
 * @var UrlGeneratorInterface                   $urlGenerator
 * @var string                                  $csrf
 * @var array                                   $class
 * @var array                                   $idpList
 * @var bool                                    $noOpenBankingContinueButton
 * @var string                                  $openBankingAuthUrl
 * @var string                                  $selectedOpenBankingProvider
 */
$this->setTitle($translator->translate('menu.signup'));
echo H::openTag('div', ['class' => (string) $class[1]]);
 echo H::openTag('div', ['class' => (string) $class[2]]);
  echo H::openTag('div', ['class' => (string) $class[3]]);
   echo H::openTag('div', ['class' => (string) $class[4]]);
    echo H::openTag('div', ['class' => (string) $class[5]]);
     echo H::openTag('h1', ['class' => (string) $class[6]]);
      echo H::encode($this->getTitle());
     echo H::closeTag('h1');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => (string) $class[7]]);
    $authChoice = AuthChoice::widget();
    /**
     * @var string $provider
     * @var array $idpList[$provider]
     * @var array $info
     * @var bool $info['noflag']
     */
    foreach ($idpList as $provider => $info) {
        $noContinueButton = $info['noflag'];
        if ($noContinueButton == false) {
            echo '<br><br>';
            echo $authChoice->absoluteButtons(
                $request,
                $idpList[$provider],
                $provider
            );
        }
    };
    $btn = new Button($currentRoute, $translator, $urlGenerator);
    if ((strlen($openBankingAuthUrl ?: '') > 0)
            && !$noOpenBankingContinueButton) {
        echo '<br><br>';
        $btn->openBanking($openBankingAuthUrl, $selectedOpenBankingProvider);
    };
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => (string) $class[10]]);
    echo Form::tag()
    ->post($urlGenerator->generate('auth/signup'))
    ->csrf($csrf)
    ->id('signupForm')
    ->open();
    echo F::text($formModel, 'login')
    ->label($translator->translate('layout.login'))
    ->autofocus();
    echo F::email($formModel, 'email')
    ->label($translator->translate('email'))
    ->autofocus();
    echo F::password($formModel, 'password')
    ->addInputAttributes(['autocomplete' => 'current-password'])
    ->label($translator->translate('layout.password'));
    echo F::password($formModel, 'passwordVerify')
    ->addInputAttributes(['autocomplete' => 'current-password'])
    ->label($translator->translate('layout.password-verify.new'));
    echo F::submitButton()
    ->buttonId('register-button')
    ->buttonClass((string) $class[15])
    ->name('register-button')
    ->content($translator->translate('layout.submit'));
    echo Form::tag()->close();
    echo H::closeTag('div'); // 5
   echo H::closeTag('div'); // 4
  echo H::closeTag('div'); // 3
 echo H::closeTag('div'); // 2
echo H::closeTag('div'); // 1
