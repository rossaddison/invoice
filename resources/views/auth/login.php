<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\A;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Yii\AuthClient\Widget\AuthChoice;

/**
 * @var App\Auth\Form\LoginForm                     $formModel
 * @var App\Invoice\Setting\SettingRepository       $s
 * @var Yiisoft\Router\CurrentRoute                 $currentRoute
 * @var Yiisoft\Router\UrlGeneratorInterface        $urlGenerator
 * @var Yiisoft\Translator\TranslatorInterface      $translator
 * @var Psr\Http\Message\ServerRequestInterface     $request
 * @var int                                         $sessionOtp
 * @var string                                      $codeChallenge
 * @var string                                      $telegramToken
 * @var string                                      $openBankingAuthUrl
 * @var array                                       $selectedIdentityProviders
 * @var string|null                                 $selectedOpenBankingProvider
 * @var bool                                        $noOpenBankingContinueButton
 * @var string                                      $csrf
 */

?>

<!-- Fade-out CSS for TFA badge -->
<?= \Yiisoft\Html\Tag\Style::tag()->content(
    '.fade-out { opacity: 1; transition: opacity 40s ease-in; } .fade-out.hidden { opacity: 0; }',
) ?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?= Html::encode($translator->translate('login')); ?></h1>
                </div>
                <div class="text-center">
                    <?php
                    /**
                     * Note: The links are authRouted.
                     * because these are absolute links that go to Identity Providers e.g. facebook
                     * ->authRoute will be used for the callbacks
                     */
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
        echo $authChoice->authRoutedButtons('auth/authclient', $selectedIdentityProviders[$provider], $provider);
    }
}; ?>
                        
                    <?php $button = new Button($currentRoute, $translator, $urlGenerator); ?>    
                    <?php if ((strlen($openBankingAuthUrl ?: '') > 0) && !$noOpenBankingContinueButton && null !== $selectedOpenBankingProvider) { ?>
                        <br><br>
                        <?= $button->openbanking($openBankingAuthUrl ?: '', $selectedOpenBankingProvider); ?>
                    <?php } ?>
                </div>
                
                <?php if (($s->getSetting('enable_tfa') == '1')) { ?>
                <div id="tfa-badge" class="card-body p-2 text-center fade-out">
                        <?=
        Html::tag(
            'span',
            $s->getSetting('enable_tfa_with_disabling') == '1'
                ? $translator->translate('two.factor.authentication.enabled.with.disabling')
                : $translator->translate('two.factor.authentication.enabled.without.disabling'),
            [
                'class' => 'badge bg-primary',
                'style' => 'white-space:normal;word-break:break-word;max-width:100%;display:inline-block;',
            ],
        ); ?>
                </div>
                <?php } ?>
                <div class="card-body p-2 text-center">
                    <?= Form::tag()
                        ->post($urlGenerator->generate('auth/login'))
                        ->class('form-floating')
                        ->csrf($csrf)
                        ->id('loginForm')
                        ->open(); ?>
                    <?= Field::text($formModel, 'login')
                        ->addInputAttributes(['autocomplete' => 'username'])
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.login')); ?>
                    <?= Field::password($formModel, 'password')
                        ->addInputAttributes(['autocomplete' => 'current-password'])
                        ->inputClass('form-control')
                        ->label($translator->translate('layout.password'));
?>
                    <?= Field::checkbox($formModel, 'rememberMe')
    ->containerClass('form-check form-switch text-start mt-2')
    ->inputClass('form-check-input form-control')
    ->label($translator->translate('layout.remember'))
    ->inputLabelClass('form-check-label')
?>
                                        <?= Field::submitButton()
    ->buttonId('login-button')
    ->buttonClass('btn btn-primary')
    ->name('login-button')
    ->content($translator->translate('layout.submit'))
?>
                    <?= Form::tag()->close(); ?>
                    <?= Html::br(); ?>
                    <?= A::tag()
    ->attribute('style', 'color:#999;text-decoration:none')
    ->addClass('my-1 mx-0')
    ->href($urlGenerator->generate('auth/forgotpassword'))
    ->content($translator->translate('forgot.your.password'))
    ->render();
?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Fade-out JS: this will fade out the badge after 2 seconds; adjust as needed -->
<?php
$fadeOutScript = <<<JS
document.addEventListener('DOMContentLoaded', function() {
    var badge = document.getElementById('tfa-badge');
    if (badge) {
        setTimeout(function() {
            badge.classList.add('hidden');
        }, 2000);
    }
});
JS;

echo \Yiisoft\Html\Html::script($fadeOutScript)->type('text/javascript')->charset('utf-8');
?>