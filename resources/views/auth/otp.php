<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var App\Auth\Form\OtpPasswordForm           $formModel
 * @var App\Invoice\Setting\SettingRepository   $s
 * @var Yiisoft\Router\CurrentRoute             $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface    $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface  $translator
 * @var Yiisoft\View\WebView                    $this
 * @var string                                  $csrf
 */

$this->setTitle($translator->translate('invoice.mtd.gov.client.multi.factor.otp'));

?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= Html::encode($this->getTitle()); ?></h1>
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/validateOtp'))
                        ->class('form-floating')
                        ->csrf($csrf)
                        ->id('otpForm')
                        ->open() ?>
                    <?= Field::password($formModel, 'otpPassword')
                        ->addInputAttributes(['autocomplete' => 'current-password'])
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.password.otp'))
                    ?>
                    <?= Field::submitButton()
                        ->buttonId('login-button')
                        ->buttonClass('btn btn-primary')
                        ->name('login-button')
                        ->content($translator->translate('layout.submit')) ?>
                    <?= Form::tag()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
