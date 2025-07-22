<?php

declare(strict_types=1);

use App\Widget\Button;
use Yiisoft\Html\Html;
use Yiisoft\View\WebView;

/*
 * @var Yiisoft\Translator\TranslatorInterface $translator
 * @var WebView $this
 * @var string $proceedToMenuButton
 */
$this->setTitle($translator->translate('identity.provider.authentication.successful'));
?>

<div class="container py-5 h-100">
    <div class="row d-flex justify-content-center align-items-center h-100">
        <div class="col-12 col-md-8 col-lg-6 col-xl-5">
            <div class="card border border-dark shadow-2-strong rounded-3">
                <div class="card-header bg-dark text-white">
                    <h1 class="fw-normal h3 text-center"><?php echo Html::encode($this->getTitle()); ?></h1>
                </div>
                <div class="text-center">
                </div>
                <div class="card-body p-5 text-center">
                    <?php echo Button::identityProviderAuthenticationSuccessful($proceedToMenuButton); ?>
                </div>
            </div>
        </div>
    </div>
</div>
