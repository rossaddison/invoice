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

/**
 * @var SignupForm                              $formModel
 * @var Yiisoft\Router\CurrentRoute             $currentRoute  
 * @var WebView                                 $this
 * @var TranslatorInterface                     $translator
 * @var UrlGeneratorInterface                   $urlGenerator
 * @var string                                  $csrf
 * @var bool                                    $noFacebookContinueButton
 * @var bool                                    $noGithubContinueButton
 * @var bool                                    $noGoogleContinueButton
 * @var bool                                    $noLinkedInContinueButton
 * @var bool                                    $noMicrosoftOnlineContinueButton
 * @var string                                  $facebookAuthUrl
 * @var string                                  $githubAuthUrl
 * @var string                                  $googleAuthUrl
 * @var string                                  $linkedInAuthUrl
 * @var string                                  $microsoftOnlineAuthUrl
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
                        ->label($translator->translate('i.email'))
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
