<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @var string $csrf
 * @var string|null $error 
 * @var App\Auth\Form\TwoFactorAuthenticationVerifyLoginForm $formModel
 * @var Yiisoft\View\WebView $this
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 */

?>

<!-- 2FA Login Verification View -->
<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">                
                <div class="card-header bg-dark text-white">
                    <h5 class="fw-normal h3 text-center"><?= $translator->translate('two.factor.authentication'); ?></h5>
                </div>
                <div class="card-body p-2 text-center">
                    <h6><?= $translator->translate('two.factor.authentication.new.six.digit.code'); ?></h6>
                    <div class="card-body p-2 text-center">
                        <?php if ((null!==$error) && (strlen($error) > 0)) { ?>
                            <?=         
                                Html::tag('span', $error, 
                                [
                                    'class' => 'badge bg-primary',
                                    'style' => 'white-space:normal;word-break:break-word;max-width:100%;display:inline-block;'
                                ]); 
                            ?>
                        <?php } ?>
                    </div>     
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/verifyLogin'))
                        ->class('form-floating')
                        ->csrf($csrf)
                        ->id('twoFactorAuthenticationVerfiyForm')
                        ->open(); ?>                    
                    <?= Field::text($formModel, 'code')
                        ->addInputAttributes(
                            [
                                'autocomplete' => 'current-code', 
                                'id' => 'code', 
                                'name' => 'text',
                                'minlength' => 6,
                                'maxlength' => 6,
                                'type' => 'tel',
                            ]
                        )
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.password.otp'))
                        ->autofocus();
                    ?>
                    <?= Field::submitButton()
                        ->buttonId('code-button')
                        ->buttonClass('btn btn-primary')
                        ->name('code-button')
                        ->content($translator->translate('layout.submit')) ?>
                    <?= Form::tag()->close() ?>
                </div>
                <div class="card-body p-1 text-center">
                    <?php for ($i = 1; $i <= 9; $i++): ?>
                        <button type="button" class="btn btn-info btn-sm btn-digit" data-digit="<?= $i ?>"><?= $i ?></button>
                    <?php endfor; ?>
                    <button type="button" class="btn btn-info btn-sm btn-digit" data-digit="0">0</button>
                    <button type="button" class="btn btn-info btn-sm btn-clear-otp">Clear</button>
                </div>
            </div>
        </div>
    </div>
</div>