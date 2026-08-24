<?php

declare(strict_types=1);

use App\Webshop\Cart\CartItem;
use App\Webshop\Checkout\CheckoutForm;
use App\Webshop\Currency\CurrencyContext;
use Yiisoft\FormModel\Field;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var CurrencyContext $currency
 * @var CheckoutForm $form
 * @var list<CartItem> $items
 * @var float $total
 * @var string $gallery Pre-rendered "Add something else" carousel — see
 *     App\Webshop\Controller\StorefrontController::productGallery().
 */

$this->setTitle('Checkout');
?>
<h1 class="mb-4">Checkout</h1>

<?= $gallery ?>

<h2 class="h5">Order summary</h2>
<ul class="list-group mb-4">
    <?php foreach ($items as $item): ?>
    <li class="list-group-item d-flex justify-content-between">
        <span><?= Html::encode($item->name) ?> &times; <?= $item->quantity ?></span>
        <span><?= Html::encode($currency->format($item->subtotal())) ?></span>
    </li>
    <?php endforeach; ?>
    <li class="list-group-item d-flex justify-content-between fw-bold">
        <span>Total</span>
        <span><?= Html::encode($currency->format($total)) ?></span>
    </li>
</ul>
<?php $currencyInfoForNotice = $currency->info(); ?>
<?php if ($currency->isShowingDocumentCurrency() && $currencyInfoForNotice !== null): ?>
<p class="text-muted small">
    Prices above are shown converted to <?= Html::encode($currencyInfoForNotice->document) ?> as an
    estimate — you will be charged in <?= Html::encode($currencyInfoForNotice->native) ?> when you place
    your order.
</p>
<?php endif; ?>

<?php
// This app's *default* Field theme (config/common/params.php's
// 'yiisoft/form'.themes.default) wraps every field in a Bootstrap
// "form-floating" container, which assumes a visible <label> is always
// present (its CSS reserves space for, and animates, that label).
// ->hideLabel() removes the label but not the floating-label
// container/height — confirmed live: every field on this page rendered
// as a tall, visually blank box with no visible placeholder text at
// all. 'bootstrap5-vertical' (also registered in that same config) is
// a plain, non-floating theme — explicitly selecting it here restores
// the minimalist placeholder-only look this form was designed for,
// without touching the floating-label theme every *staff*-facing form
// elsewhere in this app still relies on.
$theme = 'bootstrap5-vertical';
?>
<?= new Form()
    ->post($urlGenerator->generate('shop/checkout/submit'))
    ->csrf($csrf)
    ->open() ?>
<?= Field::errorSummary($form, theme: $theme)->header('') ?>
<?= Field::text($form, 'name', theme: $theme)->hideLabel()->placeholder('First name') ?>
<?= Field::text($form, 'surname', theme: $theme)->hideLabel()->placeholder('Last name') ?>
<?= Field::text($form, 'email', theme: $theme)->hideLabel()->placeholder('Email')->addInputAttributes(['type' => 'email']) ?>
<?= Field::text($form, 'address1', theme: $theme)->hideLabel()->placeholder('Address line 1') ?>
<?= Field::text($form, 'address2', theme: $theme)->hideLabel()->placeholder('Address line 2') ?>
<?= Field::text($form, 'city', theme: $theme)->hideLabel()->placeholder('City') ?>
<?= Field::text($form, 'zip', theme: $theme)->hideLabel()->placeholder('Postal / ZIP code') ?>
<?= Field::text($form, 'country', theme: $theme)->hideLabel()->placeholder('Country') ?>
<?= Field::text($form, 'phone', theme: $theme)->hideLabel()->placeholder('Phone') ?>
<?= Field::submitButton(theme: $theme)->content('Place order') ?>
<?= new Form()->close() ?>
