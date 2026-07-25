<?php

declare(strict_types=1);

use App\Auth\Form\HomeCareSignupForm;
use Yiisoft\FormModel\Field as F;
use Yiisoft\Html\Html as H;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Public HomeCare-specific signup form — separate from the generic
 * /signup. Rendered inside the site's main layout (same as signup.php), so
 * prospective customers see the normal site navigation.
 *
 * @var HomeCareSignupForm         $formModel
 * @var TranslatorInterface        $translator
 * @var UrlGeneratorInterface      $urlGenerator
 * @var string                     $csrf
 * @var array<string, list<string>> $errors
 * @var string                     $turnstileSiteKey
 * @var list<int>                  $allowedPrices
 * @var array<int, string>         $categorySecondaryOptions
 * @var array                      $class
 * @var \Yiisoft\View\WebView      $this
 */

$this->setTitle($translator->translate('homecare.signup.title'));

if ($turnstileSiteKey !== '') {
    $this->registerJsFile(
        'https://challenges.cloudflare.com/turnstile/v0/api.js',
        \Yiisoft\View\WebView::POSITION_END,
        ['async' => true, 'defer' => true],
    );
}

$priceOptions = [];
foreach ($allowedPrices as $price) {
    $priceOptions[$price] = '£' . $price;
}
?>
<?= H::openTag('div', ['class' => (string) $class[1]]) ?>
 <?= H::openTag('div', ['class' => (string) $class[2]]) ?>
  <?= H::openTag('div', ['class' => (string) $class[3]]) ?>
   <?= H::openTag('div', ['class' => (string) $class[4]]) ?>
    <?= H::openTag('div', ['class' => (string) $class[5]]) ?>
     <?= H::openTag('h1', ['class' => (string) $class[6]]) ?>
      <?= H::encode($this->getTitle()) ?>
     <?= H::closeTag('h1') ?>
    <?= H::closeTag('div') ?>
    <?= H::openTag('div', ['class' => (string) $class[10]]) ?>
     <?= new Form()
         ->post($urlGenerator->generate('homecare/signup'))
         ->csrf($csrf)
         ->id('homecareSignupForm')
         ->open() ?>
     <?= F::text($formModel, 'login')->label($translator->translate('layout.login'))->autofocus() ?>
     <?= F::email($formModel, 'email')->label($translator->translate('email')) ?>
     <?= F::password($formModel, 'password')
         ->addInputAttributes(['autocomplete' => 'new-password'])
         ->label($translator->translate('layout.password')) ?>
     <?= F::password($formModel, 'passwordVerify')
         ->addInputAttributes(['autocomplete' => 'new-password'])
         ->label($translator->translate('layout.password-verify.new')) ?>
     <?= F::text($formModel, 'clientName')->label($translator->translate('client.name')) ?>
     <?= F::text($formModel, 'clientSurname')->label($translator->translate('client.surname')) ?>
     <?= F::text($formModel, 'street')->label($translator->translate('homecare.signup.street.name')) ?>
     <?= F::text($formModel, 'buildingNumber')->label($translator->translate('client.postaladdress.building.number')) ?>
     <?= F::select($formModel, 'secondaryCategoryId')
         ->optionsData($categorySecondaryOptions)
         ->label($translator->translate('category.secondary')) ?>
     <?= F::select($formModel, 'price')
         ->optionsData($priceOptions)
         ->label($translator->translate('price')) ?>
     <?= F::radioList($formModel, 'paymentOption')
         ->items([
             HomeCareSignupForm::PAYMENT_WILL_PAY_TODAY => $translator->translate('homecare.signup.payment.will.pay.today'),
             HomeCareSignupForm::PAYMENT_HAVE_PAID_CASH => $translator->translate('homecare.signup.payment.have.paid.cash'),
         ])
         ->label($translator->translate('payment.option')) ?>
     <?= F::errorSummary($formModel)
         ->errors($errors)
         ->header($translator->translate('error.summary')) ?>
     <?php if ($turnstileSiteKey !== ''): ?>
     <div class="cf-turnstile" data-sitekey="<?= H::encode($turnstileSiteKey) ?>"></div>
     <?php endif; ?>
     <?= F::submitButton()
         ->buttonId('homecare-register-button')
         ->buttonClass((string) $class[15])
         ->name('homecare-register-button')
         ->content($translator->translate('layout.submit')) ?>
     <?= new Form()->close() ?>
    <?= H::closeTag('div') ?>
   <?= H::closeTag('div') ?>
  <?= H::closeTag('div') ?>
 <?= H::closeTag('div') ?>
<?= H::closeTag('div') ?>
