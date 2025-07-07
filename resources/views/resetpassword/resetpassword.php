<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @see App\Auth\Controller\ResetPasswordController function resetpassword
 *
 * @var App\Auth\Form\ResetPasswordForm $formModel
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * @var string $csrf
 * @var string $token
 */
$this->setTitle($translator->translate('password.reset'));
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= Html::encode($this->getTitle()) ?></h1>
                </div>
                <div class="card-body p-5 text-center">
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/resetpassword', ['token' => $token]))
                        ->csrf($csrf)
                        ->id('resetPasswordForm')
                        ->open() ?>
                    <?= Field::password($formModel, 'newPassword')
                        ->addInputAttributes(['autocomplete' => 'new-password'])
                        ->label($translator->translate('layout.password.new'));
?>
                    <?= Field::password($formModel, 'newPasswordVerify')
    ->addInputAttributes(['autocomplete' => 'verify-new-password'])
    ->label($translator->translate('layout.password-verify.new'))
?>
                    <?= Field::submitButton()
    ->buttonId('resetpassword-button')
    ->name('resetpassword-button')
    ->content($translator->translate('layout.submit'))
?>
                    <?= Form::tag()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>