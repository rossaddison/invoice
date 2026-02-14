<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\{FormModel\Field as F, Html\Html as H, Html\Tag\A, Html\Tag\Form};
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

/**
 * @var App\Auth\Form\LoginForm                     $formModel
 * @var App\Invoice\Setting\SettingRepository       $s
 * @var Yiisoft\Router\CurrentRoute                 $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface        $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface      $translator
 * @var string                                      $openBankingAuthUrl
 * @var array                                       $class
 * @var array                                       $idpList
 * @var string|null                                 $openBankChoice
 * @var bool                                        $noOpenBankingContinueButton
 * @var string                                      $csrf
 * @var string                                      $fadeOutJS
 * @var string                                      $styleTagFadeOut
 */

$styleTagFadeOut;

echo H::openTag('div', ['class' => (string) $class[1]]);
 echo H::openTag('div', ['class' => (string) $class[2]]);
  echo H::openTag('div', ['class' => (string) $class[3]]);
   echo H::openTag('div', ['class' => (string) $class[4]]);
    echo H::openTag('div', ['class' => (string) $class[5]]);
     echo H::openTag('h1', ['class' => (string) $class[6]]);
      echo H::encode($translator->translate('login'));
     echo H::closeTag('h1');
    echo H::closeTag('div');
    echo H::openTag('div', ['class' => (string) $class[7]]);
    
    /**
     * Note: The links are authRouted.
     * because these are absolute links that go to Identity Providers e.g.
     * facebook
     * ->authRoute will be used for the callbacks
     */
    $authChoice = AuthChoice::widget();

    /**
     * Selection of Identity Providers e.g. Google, Facebook for OAuth2
     * @var string $provider
     * @var array $idpList[$provider]
     * @var string $provider
     * @var array $info
     * @var bool $info['noflag']
     */
    foreach ($idpList as $provider => $info) {
        $noContinueButton = $info['noflag'];
        if ($noContinueButton == false) {
            echo '<br><br>';
            echo $authChoice->authRoutedButtons(
                'auth/authclient',
                $idpList[$provider],
                $provider
        );
        }
    }; 

    $btn = new Button($currentRoute, $translator, $urlGenerator);
    $tfaEnabled = 'two.factor.authentication.enabled';
    if ((strlen($openBankingAuthUrl ?: '') > 0)
            && !$noOpenBankingContinueButton
            && null !== $openBankChoice) {
                echo '<br><br>';
                $btn->openbanking($openBankingAuthUrl ?: '', $openBankChoice);
    } 
    
    echo H::closeTag('div');
    if (($s->getSetting('enable_tfa') == '1')) {
      echo H::openTag('div', [
          'id' => 'tfa-badge', 'class' => (string) $class[8]]);
        echo H::tag(
            'span',
            $s->getSetting('enable_tfa_with_disabling') == '1'
                ? $translator->translate($tfaEnabled . '.with.disabling')
                : $translator->translate($tfaEnabled . '.without.disabling'),
            [
                'class' => (string) $class[9],
                'style' => 'white-space:normal;word-break:break-word;'
                . 'max-width:100%;display:inline-block;',
            ],
        );
        echo H::closeTag('div');
    }
    echo H::openTag('div', ['class' => (string) $class[10]]);
    echo Form::tag()
    ->post($urlGenerator->generate('auth/login'))
    ->class('form-floating')
    ->csrf($csrf)
    ->id('loginForm')
    ->open();
    echo F::text($formModel, 'login')
    ->addInputAttributes(['autocomplete' => 'username'])
    ->inputClass((string) $class[11])
    ->label($translator->translate('layout.login'));
    echo F::password($formModel, 'password')
    ->addInputAttributes(['autocomplete' => 'current-password'])
    ->inputClass((string) $class[11])
    ->label($translator->translate('layout.password'));
    echo F::checkbox($formModel, 'rememberMe')
    ->containerClass((string) $class[12])
    ->inputClass((string) $class[13])
    ->label($translator->translate('layout.remember'))
    ->inputLabelClass((string) $class[14]);
    echo F::submitButton()
    ->buttonId('login-button')
    ->buttonClass((string) $class[15])
    ->name('login-button')
    ->content($translator->translate('layout.submit'));
    echo Form::tag()->close();
    echo H::br();
    echo A::tag()
    ->attribute('style', 'color:#999;text-decoration:none')
    ->addClass((string) $class[16])
    ->href($urlGenerator->generate('auth/forgotpassword'))
    ->content($translator->translate('forgot.your.password'))
    ->render();
    echo H::closeTag('div'); // 5
   echo H::closeTag('div'); // 4
  echo H::closeTag('div'); // 3
 echo H::closeTag('div'); // 2
echo H::closeTag('div'); // 1
echo $fadeOutJS;
