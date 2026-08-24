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
 */

$this->setTitle('Checkout');
?>
<h1 class="mb-4">Checkout</h1>

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

<?= new Form()
    ->post($urlGenerator->generate('shop/checkout/submit'))
    ->csrf($csrf)
    ->open() ?>
<?= Field::errorSummary($form)->header('') ?>
<?= Field::text($form, 'name')->hideLabel()->placeholder('First name') ?>
<?= Field::text($form, 'surname')->hideLabel()->placeholder('Last name') ?>
<?= Field::text($form, 'email')->hideLabel()->placeholder('Email')->addInputAttributes(['type' => 'email']) ?>
<?= Field::text($form, 'address1')->hideLabel()->placeholder('Address line 1') ?>
<?= Field::text($form, 'address2')->hideLabel()->placeholder('Address line 2') ?>
<?= Field::text($form, 'city')->hideLabel()->placeholder('City') ?>
<?= Field::text($form, 'zip')->hideLabel()->placeholder('Postal / ZIP code') ?>
<?= Field::text($form, 'country')->hideLabel()->placeholder('Country') ?>
<?= Field::text($form, 'phone')->hideLabel()->placeholder('Phone') ?>
<?= Field::submitButton()->content('Place order') ?>
<?= new Form()->close() ?>
