<?php

declare(strict_types=1);

use App\Webshop\Cart\Asset\CartAsset;
use App\Webshop\Cart\CartItem;
use App\Webshop\Currency\CurrencyContext;
use Yiisoft\Assets\AssetManager;
use Yiisoft\Html\Html;
use Yiisoft\Html\Tag\Form;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\View\WebView;
use Yiisoft\Yii\View\Renderer\Csrf;

/**
 * @var WebView $this
 * @var UrlGeneratorInterface $urlGenerator
 * @var Csrf $csrf
 * @var AssetManager $assetManager
 * @var CurrencyContext $currency
 * @var list<CartItem> $items
 * @var float $total
 * @var string $gallery Pre-rendered "Add something else" carousel — see
 *     App\Webshop\Controller\StorefrontController::productGallery().
 */

$this->setTitle('Cart');
?>
<h1 class="mb-4">Your cart</h1>
<?= $gallery ?>
<?php if ($items === []): ?>
    <p class="text-muted">Your cart is empty.</p>
    <?= Html::a('Browse products', $urlGenerator->generate('shop/catalog/index'), ['class' => 'btn btn-primary']) ?>
<?php else:
    // Registered here rather than in the storefront layout — nothing
    // else on the site needs it, and this view's own registration runs
    // before the layout collects $assetManager->getJsFiles() (see
    // CartAsset's own docblock).
    $assetManager->register(CartAsset::class);
    $this->addJsFiles($assetManager->getJsFiles());
?>
<div
    id="cart-table-wrapper"
    data-csrf-token="<?= Html::encode($csrf->getToken()) ?>"
    data-csrf-header="<?= Html::encode($csrf->getHeaderName()) ?>"
>
<table class="table align-middle">
    <thead>
        <tr>
            <th>Product</th>
            <th>Price</th>
            <th style="width: 8rem;">Quantity</th>
            <th>Subtotal</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr data-product-id="<?= (string) $item->productId ?>">
            <td><?= Html::encode($item->name) ?></td>
            <td><?= Html::encode($currency->format($item->price)) ?></td>
            <td>
                <?= new Form()
                    ->post($urlGenerator->generate('shop/cart/update'))
                    ->csrf($csrf)
                    ->addAttributes(['class' => 'd-flex gap-2 js-cart-update-form'])
                    ->open() ?>
                <?= Html::hiddenInput('product_id', (string) $item->productId) ?>
                <?= Html::input('number', 'quantity', (string) $item->quantity, ['min' => '0', 'step' => '1', 'class' => 'form-control form-control-sm js-cart-qty', 'style' => 'width: 5rem;']) ?>
                <button type="submit" class="btn btn-outline-secondary btn-sm">Update</button>
                <?= new Form()->close() ?>
            </td>
            <td class="js-cart-subtotal"><?= Html::encode($currency->format($item->subtotal())) ?></td>
            <td>
                <?= new Form()
                    ->post($urlGenerator->generate('shop/cart/remove', ['id' => (string) $item->productId]))
                    ->csrf($csrf)
                    ->addAttributes(['class' => 'js-cart-remove-form'])
                    ->open() ?>
                <button type="submit" class="btn btn-outline-danger btn-sm">Remove</button>
                <?= new Form()->close() ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th colspan="3" class="text-end">Total</th>
            <th class="js-cart-total"><?= Html::encode($currency->format($total)) ?></th>
            <th></th>
        </tr>
    </tfoot>
</table>
</div>
<?= Html::a('Checkout', $urlGenerator->generate('shop/checkout/index'), ['class' => 'btn btn-primary']) ?>
<?php endif; ?>
