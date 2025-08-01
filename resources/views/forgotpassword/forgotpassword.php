<?php

declare(strict_types=1);

use App\Auth\Form\RequestPasswordResetTokenForm;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView                         $this
 * @var TranslatorInterface             $translator
 * @var UrlGeneratorInterface           $urlGenerator
 * @var string                          $csrf
 * @var RequestPasswordResetTokenForm   $formModel
 */
$this->setTitle($translator->translate('password.reset.request.token'));
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
                        ->post($urlGenerator->generate('auth/forgotpassword'))
                        ->csrf($csrf)
                        ->id('requestPasswordResetTokenForm')
                        ->open();
?>
                    <?= Field::email($formModel, 'email')
    ->label($translator->translate('email'))
    ->autofocus()
?>
                    <?= Field::submitButton()
    ->buttonId('password-reset-token-button')
    ->name('password-reset-token-button')
    ->content($translator->translate('layout.submit'))
?>
                    <?= Form::tag()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
