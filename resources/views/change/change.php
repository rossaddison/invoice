<?php

declare(strict_types=1);

use App\Auth\Form\ChangeForm;
use Yiisoft\Form\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\View\WebView;

/**
 * @var WebView               $this
 * @var TranslatorInterface   $translator
 * @var UrlGeneratorInterface $urlGenerator
 * @var string                $csrf
 * @var ChangeForm             $formModel
 */
$this->setTitle($translator->translate('change'));
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
                        // note: the chagne function actually appears in the ChangeController
                        ->post($urlGenerator->generate('auth/change'))
                        ->csrf($csrf)
                        ->id('changeForm')
                        ->open() ?>

                    <?= Field::text($formModel, 'login')->addInputAttributes(['value'=> $login ?? '', 'readonly'=>'readonly']) ?>
                    <?= Field::password($formModel, 'password') ?>
                    <?= Field::password($formModel, 'passwordVerify') ?>
                    <?= Field::password($formModel, 'newPassword') ?>
                    <?= Field::password($formModel, 'newPasswordVerify') ?>
                    <?= Field::submitButton()
                        ->buttonId('change-button')
                        ->name('change-button')
                        ->content($translator->translate('layout.submit'))
                    ?>
                    <?= Form::tag()->close() ?>
                </div>
            </div>
        </div>
    </div>
</div>