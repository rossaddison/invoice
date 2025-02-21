<?php

declare(strict_types=1);

use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;

/**
 * @see App\Auth\Controller\ChangePasswordController function change
 * 
 * @var App\Auth\Form\ChangePasswordForm $formModel 
 * @var Yiisoft\Router\UrlGeneratorInterface $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var Yiisoft\View\WebView $this
 * 
 * @see resources\rbac\items.php admin permissions 
 * @var bool $changePasswordForAnyUser
 * 
 * @var string $csrf
 */
$this->setTitle($translator->translate('password.change'));
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
                        // note: the change function actually appears in the ChangePasswordController
                        ->post($urlGenerator->generate('auth/change'))
                        ->csrf($csrf)
                        ->id('changePasswordForm')
                        ->open() ?>
                    <?= $changePasswordForAnyUser  
                            ?   Field::text($formModel, 'login')
                                ->label($translator->translate('layout.login'))
                                ->addInputAttributes([
                                    'autocomplete' => 'username',
                                    'value' => $login ?? ''
                                ]) 
                            :   Field::text($formModel, 'login')
                                ->label($translator->translate('layout.login'))
                                ->addInputAttributes([
                                    'autocomplete' => 'username',
                                    'value' => $login ?? '', 
                                    'readonly' => 'readonly'
                                ]); 
                    ?>
                    <?= Field::password($formModel, 'password')
                        ->addInputAttributes(['autocomplete' => 'current-password'])    
                        ->label($translator->translate('layout.password'));        
                    ?>
                    <?= Field::password($formModel, 'newPassword') 
                        ->addInputAttributes(['autocomplete' => 'new-password'])
                        ->label($translator->translate('layout.password.new'));    
                    ?>
                    <?= Field::password($formModel, 'newPasswordVerify')
                        ->addInputAttributes(['autocomplete' => 'verify-password'])    
                        ->label($translator->translate('layout.password-verify.new'))    
                    ?>
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