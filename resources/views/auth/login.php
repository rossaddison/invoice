<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Auth\Form\LoginForm                 $formModel
 * @var App\Invoice\Setting\SettingRepository   $s
 * @var Yiisoft\Router\CurrentRoute             $currentRoute 
 * @var Yiisoft\Router\UrlGeneratorInterface    $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface  $translator
 * @var Yiisoft\View\WebView                    $this
 * @var string                                  $facebookAuthUrl
 * @var string                                  $githubAuthUrl
 * @var string                                  $googleAuthUrl
 * @var string                                  $linkedInAuthUrl 
 * @var string                                  $microsoftOnlineAuthUrl
 * @var string                                  $vkontakteAuthUrl
 * @var string                                  $xAuthUrl
 * @var string                                  $yandexAuthUrl
 * @var bool                                    $noFacebookContinueButton
 * @var bool                                    $noGithubContinueButton
 * @var bool                                    $noGoogleContinueButton
 * @var bool                                    $noLinkedInContinueButton
 * @var bool                                    $noMicrosoftOnlineContinueButton
 * @var bool                                    $noVKontakteContinueButton
 * @var bool                                    $noXContinueButton
 * @var bool                                    $noYandexContinueButton
 * @var string                                  $csrf
 */

$this->setTitle($translator->translate('i.login'));

?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= Html::encode($this->getTitle()); ?></h1>
                </div>
                <div class="text-center">
                    <?php $button = new Button($currentRoute, $translator, $urlGenerator); ?>
                    <?php if ((strlen($facebookAuthUrl ?: '') > 0) && !$noFacebookContinueButton) { ?>
                        <br><br>
                        <?= $button->facebook($facebookAuthUrl); ?>
                    <?php } ?>
                    <?php if ((strlen($githubAuthUrl ?: '') > 0) && !$noGithubContinueButton) { ?>
                        <br><br>
                        <?= $button->github($githubAuthUrl ?: ''); ?>
                    <?php } ?>    
                    <?php if ((strlen($googleAuthUrl ?: '') > 0) && !$noGoogleContinueButton) { ?>
                        <br><br>
                        <?= $button->google($googleAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($linkedInAuthUrl ?: '') > 0) && !$noLinkedInContinueButton) { ?>
                        <br><br>
                        <?= $button->linkedin($linkedInAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($microsoftOnlineAuthUrl ?: '') > 0) && !$noMicrosoftOnlineContinueButton) { ?>
                        <br><br>
                        <?= $button->microsoftonline($microsoftOnlineAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($vkontakteAuthUrl ?: '') > 0) && !$noVKontakteContinueButton) { ?>
                        <br><br>
                        <?= $button->vkontakte($vkontakteAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($xAuthUrl ?: '') > 0) && !$noXContinueButton) { ?>
                        <br><br>
                        <?= $button->x($xAuthUrl ?: ''); ?>
                    <?php } ?>
                    <?php if ((strlen($yandexAuthUrl ?: '') > 0) && !$noYandexContinueButton) { ?>
                        <br><br>
                        <?= $button->yandex($yandexAuthUrl ?: ''); ?>
                    <?php } ?>       
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/login'))
                        ->class('form-floating')
                        ->csrf($csrf)
                        ->id('loginForm')
                        ->open() ?>
                    <?= Field::text($formModel, 'login')
                        ->addInputAttributes(['autocomplete' => 'username'])        
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.login'))
                        ->autofocus() ?>
                    <?= Field::password($formModel, 'password')
                        ->addInputAttributes(['autocomplete' => 'current-password'])    
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.password'))
                    ?>
                    <?= Field::checkbox($formModel, 'rememberMe')
                        ->containerClass('form-check form-switch text-start mt-2')
                        ->inputClass('form-check-input form-control')
                        ->label($translator->translate('layout.remember'))
                        ->inputLabelClass('form-check-label') ?>
                    <?= Field::submitButton()
                        ->buttonId('login-button')
                        ->buttonClass('btn btn-primary')
                        ->name('login-button')
                        ->content($translator->translate('layout.submit')) ?>
                    <?= Form::tag()->close() ?>
                    <?= Html::br(); ?>
                    <?= A::tag()
                        ->attribute('style', 'color:#999;text-decoration:none')
                        ->addClass('my-1 mx-0')    
                        ->href($urlGenerator->generate('auth/forgotpassword'))
                        ->content($translator->translate('i.forgot_your_password'))
                        ->render();
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
