<?php

declare(strict_types=1);

namespace App\Webshop\Checkout;

use App\Api\OrderService;
use App\Service\WebControllerService;
use App\Webshop\Cart\CartService;
use App\Webshop\Controller\StorefrontController;
use App\Webshop\Delivery\DeliveryAddressService;
use App\Webshop\StorefrontViewParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\FormModel\FormHydrator;
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
    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CartService $cartService,
        private readonly TranslatorInterface $translator,
        private readonly Flash $flash,
        private readonly DeliveryAddressService $deliveryAddressService,
        private readonly StorefrontViewParameters $chrome,
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

        return $this->render('index', [
            ...$this->chrome->getLayoutParameters(),
            'form' => $form,
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
        ]);
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
            return $this->render('index', [
                ...$this->chrome->getLayoutParameters(),
                'form' => $form,
                'items' => $this->cartService->getItems(),
                'total' => $this->cartService->getTotal(),
            ]);
        }

        $items = [];
        foreach ($this->cartService->getItems() as $item) {
            $items[] = ['product_id' => $item->productId, 'quantity' => $item->quantity];
        }

        $invId = $orderService->createOrder($form->toCustomerArray(), $items);
        if ($invId === null) {
            $this->flash->add('danger', $this->translator->translate('checkout.failed'), true);
            return $this->webService->getRedirectResponse('shop/checkout/index');
        }

        $this->cartService->clear();

        // The submitting session is already authenticated as the
        // resolved customer account by the time createOrder() returns
        // (see OrderService's own docblock) — a plain named-route
        // redirect is enough, no external/cross-app URL involved.
        return $this->webService->getRedirectResponse('inv/view', ['_language' => 'en', 'id' => (string) $invId]);
    }
}
