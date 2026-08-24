<?php

declare(strict_types=1);

namespace App\Webshop\Checkout;

use App\Api\OrderService;
use App\Invoice\Enum\FlashScope;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\Webshop\Cart\CartService;
use App\Webshop\Catalog\CatalogQueryService;
use App\Webshop\Controller\StorefrontController;
use App\Webshop\Delivery\DeliveryAddressService;
use App\Webshop\StorefrontViewParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

/**
 * Checkout — the one storefront controller with a real side effect
 * beyond session state: `submit()` calls `App\Api\OrderService::
 * createOrder()` directly (in-process now, not the standalone webshop
 * app's `POST /api/orders` HTTP call), which creates a real Client + Inv
 * + InvItems and logs this request's own session in as the resolved
 * customer account — see that class's own docblock for why a one-time
 * login-link handoff is no longer needed. `submit()` therefore redirects
 * straight to `inv/view/{id}` on success, already authenticated.
 */
final class CheckoutController extends StorefrontController
{
    use FlashMessage;

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CartService $cartService,
        private readonly TranslatorInterface $translator,
        // Not read directly in this class — the FlashMessage trait's own
        // flashMessage() method reads/writes $this->flash. Flagged as an
        // "unused private field" by tools that don't resolve trait method
        // bodies against the host class; it's genuinely used.
        private readonly Flash $flash,
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly StorefrontViewParameters $chrome,
        private readonly CatalogQueryService $catalog,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
        parent::__construct($webViewRenderer, 'shop/checkout');
    }

    public function index(CheckoutForm $form): ResponseInterface
    {
        if ($this->cartService->isEmpty()) {
            return $this->webService->getRedirectResponse('shop/cart/index');
        }

        // Saves retyping name/city/postcode if the navbar's "Deliver
        // to" widget already has them.
        $delivery = $this->deliveryAddressService->get();
        if ($delivery !== null) {
            $form->fillFromDeliveryAddress($delivery);
        }

        return $this->renderForm($form);
    }

    public function submit(
        ServerRequestInterface $request,
        CheckoutForm $form,
        FormHydrator $formHydrator,
        OrderService $orderService,
    ): ResponseInterface {
        if ($this->cartService->isEmpty()) {
            return $this->webService->getRedirectResponse('shop/cart/index');
        }

        if (!$formHydrator->populateFromPostAndValidate($form, $request)) {
            return $this->renderForm($form);
        }

        return $this->completeOrder($form, $orderService);
    }

    /**
     * Split out of submit() purely to keep its own return count within
     * SonarQube's limit (php:S1142) — the cart-empty/validation-failure
     * guard clauses above both happen before any order-creation side
     * effect, so this only needs to cover order creation's own two
     * outcomes.
     */
    private function completeOrder(CheckoutForm $form, OrderService $orderService): ResponseInterface
    {
        $items = [];
        foreach ($this->cartService->getItems() as $item) {
            $items[] = ['product_id' => $item->productId, 'quantity' => $item->quantity];
        }

        $invId = $orderService->createOrder($form->toCustomerArray(), $items);
        if ($invId === null) {
            $this->flashMessage('danger', $this->translator->translate('checkout.failed'), FlashScope::Shop);
            return $this->webService->getRedirectResponse('shop/checkout/index');
        }

        $this->cartService->clear();

        // The submitting session is already authenticated as the
        // resolved customer account by the time createOrder() returns
        // (see OrderService's own docblock) — a plain named-route
        // redirect is enough, no external/cross-app URL involved.
        return $this->webService->getRedirectResponse('inv/view', ['_language' => 'en', 'id' => (string) $invId]);
    }

    /**
     * Shared by index()'s success path and submit()'s validation-failure
     * path — both render the exact same page, just with the form in a
     * different state. Includes the "Add something else" gallery with a
     * $returnTo of this page itself, so adding one more product from here
     * (see resources/views/shop/_shared/product_gallery.php) lands the
     * customer straight back on checkout, not the cart page.
     */
    private function renderForm(CheckoutForm $form): ResponseInterface
    {
        $chrome = $this->chrome->getLayoutParameters();
        $currency = $chrome['currency'];

        return $this->render('index', [
            ...$chrome,
            'form' => $form,
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
            'gallery' => $this->productGallery(
                $this->catalog->listAll(),
                $currency,
                $this->urlGenerator->generate('shop/checkout/index'),
            ),
        ]);
    }
}
