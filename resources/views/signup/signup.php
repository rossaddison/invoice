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

/*
 * @var SignupForm                              $formModel
 * @var Yiisoft\Router\CurrentRoute             $currentRoute
 * @var WebView                                 $this
 * @var TranslatorInterface                     $translator
 * @var UrlGeneratorInterface                   $urlGenerator
 * @var string                                  $csrf
 * @var bool                                    $noDeveloperSandboxHmrcContinueButton
 * @var bool                                    $noFacebookContinueButton
 * @var bool                                    $noGithubContinueButton
 * @var bool                                    $noGoogleContinueButton
 * @var bool                                    $noGovUkContinueButton
 * @var bool                                    $noLinkedInContinueButton
 * @var bool                                    $noMicrosoftOnlineContinueButton
 * @var bool                                    $noVKontakteContinueButton
 * @var bool                                    $noXContinueButton
 * @var bool                                    $noYandexContinueButton
 * @var int                                     $sessionOtp
 * @var string                                  $developerSandboxHmrcAuthUrl
 * @var string                                  $facebookAuthUrl
 * @var string                                  $githubAuthUrl
 * @var string                                  $googleAuthUrl
 * @var string                                  $govUkAuthUrl
 * @var string                                  $linkedInAuthUrl
 * @var string                                  $microsoftOnlineAuthUrl
 * @var string                                  $telegramToken
 * @var string                                  $vkontakteAuthUrl
 * @var string                                  $xAuthUrl
 * @var string                                  $yandexAuthUrl
 */
$this->setTitle($translator->translate('menu.signup'));
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?php echo Html::encode($this->getTitle()); ?></h1>
                </div>
                <div class="text-center">
                    <?php $button = new Button($currentRoute, $translator, $urlGenerator); ?>
                     <?php if ((strlen($developerSandboxHmrcAuthUrl ?: '') > 0) && !$noDeveloperSandboxHmrcContinueButton) { ?>
                        <br><br>
                        <?php echo $button->developerSandboxHmrc($developerSandboxHmrcAuthUrl); ?>
                    <?php } ?>
                    <?php if ((strlen($facebookAuthUrl ?: '') > 0) && !$noFacebookContinueButton) { ?>
                        <br><br>
                        <?php echo $button->facebook($facebookAuthUrl); ?>
                    <?php } ?>
                    <?php if ((strlen($githubAuthUrl ?: '') > 0) && !$noGithubContinueButton) { ?>
                        <br><br>
                        <?php echo $button->github($githubAuthUrl ?: ''); ?>
                    <?php } ?>    
                    <?php if ((strlen($googleAuthUrl ?: '') > 0) && !$noGoogleContinueButton) { ?>
                        <br><br>
                        <?php echo $button->google($googleAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($govUkAuthUrl ?: '') > 0) && !$noGovUkContinueButton) { ?>
                        <br><br>
                        <?php echo $button->govuk($govUkAuthUrl ?: ''); ?>
                    <?php } ?>    
                    <?php if ((strlen($linkedInAuthUrl ?: '') > 0) && !$noLinkedInContinueButton) { ?>
                        <br><br>
                        <?php echo $button->linkedin($linkedInAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($microsoftOnlineAuthUrl ?: '') > 0) && !$noMicrosoftOnlineContinueButton) { ?>
                        <br><br>
                        <?php echo $button->microsoftonline($microsoftOnlineAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($vkontakteAuthUrl ?: '') > 0) && !$noVKontakteContinueButton) { ?>
                        <br><br>
                        <?php echo $button->vkontakte($vkontakteAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($xAuthUrl ?: '') > 0) && !$noXContinueButton) { ?>
                        <br><br>
                        <?php echo $button->x($xAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($yandexAuthUrl ?: '') > 0) && !$noYandexContinueButton) { ?>
                        <br><br>
                        <?php echo $button->yandex($yandexAuthUrl ?: ''); ?>
                    <?php } ?>       
                </div>
                <div class="card-body p-5 text-center">
                    <?php echo Form::tag()
                        ->post($urlGenerator->generate('auth/signup'))
                        ->csrf($csrf)
                        ->id('signupForm')
                        ->open();
?>
                    <?php echo Field::text($formModel, 'login')
    ->label($translator->translate('layout.login'))
    ->autofocus();
?>
                    <?php echo Field::email($formModel, 'email')
    ->label($translator->translate('email'))
    ->autofocus();
?>
                    <?php echo Field::password($formModel, 'password')
    ->label($translator->translate('layout.password'));
?>
                    <?php echo Field::password($formModel, 'passwordVerify')
    ->label($translator->translate('layout.password-verify.new'));
?>
                    <?php echo Field::submitButton()
    ->buttonId('register-button')
    ->name('register-button')
    ->content($translator->translate('layout.submit'));
?>
                    <?php echo Form::tag()->close(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
