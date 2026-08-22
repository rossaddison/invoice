<?php

declare(strict_types=1);

use App\Widget\Button;
use App\Widget\IdentityProviderButton;
use Yiisoft\{FormModel\Field as F};
use Yiisoft\Html\{Html as H, Tag\A, Tag\Form, Tag\Span};
use Yiisoft\View\WebView;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

/**
 * @var WebView                                     $this
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
 * @var string                                      $styleTagFadeOut
 * @var array<string, list<string>>                 $errors
 */

$styleTagFadeOut;

$turnstileSiteKey = $s->getSetting('turnstile_site_key');
if ($turnstileSiteKey !== '') {
    $this->registerJsFile(
        'https://challenges.cloudflare.com/turnstile/v0/api.js',
        WebView::POSITION_END,
        ['async' => true, 'defer' => true],
    );
}

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

    $btn = new IdentityProviderButton($translator, $urlGenerator);
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
        echo  new Span()
             ->addAttributes([
                 'class' => (string) $class[9],
                 'style' => 'white-space:normal;word-break:break-word;'
                . 'max-width:100%;display:inline-block;',
                 'data-toggle-bs' => 'tooltip',
                 'title' => $s->getSetting('enable_tfa_with_disabling') == '1'
                ? $translator->translate($tfaEnabled . '.with.disabling')
                : $translator->translate($tfaEnabled . '.without.disabling'
             )])
             ->content($translator->translate($tfaEnabled . '.badge'))
             ->render();
        echo H::openTag('br');
        echo  new Span()
         ->addAttributes(['class' => 'text-muted small'])
         ->content($translator->translate('two.factor.authentication.compatible.apps'))
         ->render();
      echo H::closeTag('div');
    }
    echo H::openTag('div', ['class' => (string) $class[10]]);
    echo  new Form()
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
    echo F::errorSummary($formModel)
    ->errors($errors)
    ->header($translator->translate('error.summary'));
    if ($turnstileSiteKey !== '') {
        echo H::tag('div', '', ['class' => 'cf-turnstile', 'data-sitekey' => $turnstileSiteKey]);
    }
    echo F::submitButton()
    ->buttonId('login-button')
    ->buttonClass((string) $class[15])
    ->name('login-button')
    ->content($translator->translate('layout.submit'));
    echo  new Form()->close();
    echo H::br();
    echo  new A()
    ->attribute('style', 'color:#999')
    ->addClass('text-decoration-none')
    ->addClass((string) $class[16])
    ->href($urlGenerator->generate('auth/forgotpassword'))
    ->content($translator->translate('forgot.your.password'))
    ->render();
    echo H::closeTag('div'); // 5
   echo H::closeTag('div'); // 4
  echo H::closeTag('div'); // 3
 echo H::closeTag('div'); // 2
echo H::closeTag('div'); // 1
// tfa-badge fade-out moved to src/Auth/Asset/keypad-copy-to-clipboard.ts
// (bundled into keypad-copy-to-clipboard-iife.js, already loaded on this
// page) so script-src no longer needs 'unsafe-inline'.
