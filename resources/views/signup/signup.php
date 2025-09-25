<?php

declare(strict_types=1);

use App\Auth\Form\SignupForm;
use App\Widget\Button;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

/**
 * @var Psr\Http\Message\ServerRequestInterface $request
 * @var SignupForm                              $formModel
 * @var Yiisoft\Router\CurrentRoute             $currentRoute
 * @var WebView                                 $this
 * @var TranslatorInterface                     $translator
 * @var UrlGeneratorInterface                   $urlGenerator
 * @var string                                  $csrf
 * @var bool                                    $noGovUkContinueButton
 * @var bool                                    $noDeveloperSandboxHmrcContinueButton
 * @var bool                                    $noOpenBankingContinueButton
 * @var int                                     $sessionOtp
 * @var string                                  $developerSandboxHmrcAuthUrl
 * @var string                                  $govUkAuthUrl
 * @var string                                  $openBankingAuthUrl
 * @var string                                  $selectedOpenBankingProvider
 * @var string                                  $telegramToken
 * @var array                                   $selectedIdentityProviders
 */
$this->setTitle($translator->translate('menu.signup'));
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= Html::encode($this->getTitle()) ?></h1>
                </div>
                <div class="text-center">
                    <?php
                    $authChoice = AuthChoice::widget();

/**
 * @var string $provider
 * @var array $selectedIdentityProviders[$provider]
 * @var string $provider
 * @var array $info
 * @var bool $info['noflag']
 */
foreach ($selectedIdentityProviders as $provider => $info) {
    $noContinueButton = $info['noflag'];
    if ($noContinueButton == false) {
        echo '<br><br>';
        echo $authChoice->absoluteButtons($request, $selectedIdentityProviders[$provider], $provider);
    }
}; ?>
                    
                    <?php $button = new Button($currentRoute, $translator, $urlGenerator); ?>    
                    <?php if ((strlen($openBankingAuthUrl ?: '') > 0) && !$noOpenBankingContinueButton) { ?>
                        <br><br>
                        <?= $button->openBanking($openBankingAuthUrl, $selectedOpenBankingProvider); ?>
                    <?php } ?>    
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
    ->post($urlGenerator->generate('auth/signup'))
    ->csrf($csrf)
    ->id('signupForm')
    ->open();
?>
                    <?= Field::text($formModel, 'login')
    ->label($translator->translate('layout.login'))
    ->autofocus()
?>
                    <?= Field::email($formModel, 'email')
    ->label($translator->translate('email'))
    ->autofocus()
?>
                    <?= Field::password($formModel, 'password')
    ->label($translator->translate('layout.password'))
?>
                    <?= Field::password($formModel, 'passwordVerify')
    ->label($translator->translate('layout.password-verify.new'))
?>
                    <?= Field::submitButton()
    ->buttonId('register-button')
    ->name('register-button')
    ->content($translator->translate('layout.submit'))
?>
                    <?= Form::tag()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
