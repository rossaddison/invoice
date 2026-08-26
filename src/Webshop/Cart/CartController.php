<?php

declare(strict_types=1);

namespace App\Webshop\Cart;

use App\Invoice\Enum\FlashScope;
use App\Invoice\Traits\FlashMessage;
use App\Service\WebControllerService;
use App\Webshop\Catalog\CatalogQueryService;
use App\Webshop\Catalog\ProductListing;
use App\Webshop\Controller\StorefrontController;
use App\Webshop\Currency\CurrencyContext;
use App\Webshop\StorefrontViewParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Header;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Session\Flash\Flash;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CartController extends StorefrontController
{
    use FlashMessage;

    private const string CART_INDEX_ROUTE = 'shop/cart/index';

    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CartService $cartService,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly CurrencyContext $currency,
        private readonly StorefrontViewParameters $chrome,
        private readonly CatalogQueryService $catalog,
        private readonly TranslatorInterface $translator,
        // Not read directly in this class — FlashMessage's own
        // flashMessage() method reads/writes $this->flash (see
        // CheckoutController's identical precedent).
        private readonly Flash $flash,
    ) {
        parent::__construct($webViewRenderer, 'shop/cart');
    }

    public function index(): ResponseInterface
    {
        return $this->render('index', [
            ...$this->chrome->getLayoutParameters(),
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
            // No $returnTo — CartController::add()'s own default
            // (shop/cart/index) is already the right landing spot when
            // this partial is rendered on the cart page itself.
            'gallery' => $this->productGallery($this->catalog->listAll(), $this->currency),
        ]);
    }

    /**
     * Re-resolves name/price from the catalog rather than trusting the
     * submitted form — the same "catalog is the only source of truth for
     * price" principle `App\Api\OrderService` already enforces for the
     * cart-to-order handoff. Requested quantity (cumulative with whatever
     * is already in the cart) is clamped to `ProductListing::availableStock`
     * the same way — this is UX only, never the enforcement boundary; see
     * `App\Api\OrderService::addOrderItem()`'s own authoritative check.
     */
    public function add(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 1);
        // Set by shop/catalog/view.php's own hidden `redirect` field only
        // when that page was reached via a gallery that passed one along
        // (see resources/views/shop/_shared/product_gallery.php) — e.g.
        // "add something else" from checkout. Empty/absent keeps the
        // long-standing plain-catalog-browsing default: always back to
        // the cart page itself.
        $redirect = (string) ($body['redirect'] ?? '');

        $product = $this->catalog->find($productId);
        if ($product !== null && $quantity > 0.0) {
            $this->addClampedToStock($product, $quantity);
        }

        return $redirect !== ''
            ? $this->webService->getRedirectToSameOriginPathResponse($redirect)
            : $this->webService->getRedirectResponse(self::CART_INDEX_ROUTE);
    }

    private function addClampedToStock(ProductListing $product, float $requestedQuantity): void
    {
        $currentQuantity = $this->currentCartQuantity($product->id);
        $requestedTotal = $currentQuantity + $requestedQuantity;
        $allowedTotal = $product->availableStock !== null
            ? min($requestedTotal, $product->availableStock)
            : $requestedTotal;

        $delta = $allowedTotal - $currentQuantity;
        if ($delta > 0.0) {
            $this->cartService->add($product->id, $product->displayName(), $product->price, $delta);
        }
        if ($allowedTotal < $requestedTotal) {
            $this->flashMessage('warning', $this->translator->translate('cart.insufficient.stock'), FlashScope::Shop);
        }
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 0);

        $product = $this->catalog->find($productId);
        $availableStock = $product?->availableStock;
        $allowedQuantity = $availableStock !== null ? min($quantity, $availableStock) : $quantity;
        $clamped = $allowedQuantity < $quantity;
        if ($clamped) {
            $this->flashMessage('warning', $this->translator->translate('cart.insufficient.stock'), FlashScope::Shop);
        }

        $this->cartService->updateQuantity($productId, $allowedQuantity);

        return $this->wantsJson($request)
            ? $this->jsonCartResponse($productId, $clamped)
            : $this->webService->getRedirectResponse(self::CART_INDEX_ROUTE);
    }

    /** Matches jsonCartResponse()'s own linear-search-by-productId shape. */
    private function currentCartQuantity(int $productId): float
    {
        foreach ($this->cartService->getItems() as $item) {
            if ($item->productId === $productId) {
                return $item->quantity;
            }
        }
        return 0.0;
    }

    public function remove(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $this->cartService->remove($id);

        return $this->wantsJson($request)
            ? $this->jsonCartResponse($id)
            : $this->webService->getRedirectResponse(self::CART_INDEX_ROUTE);
    }

    /**
     * `src/Webshop/Cart/Asset/cart.ts` sends `Accept: application/json`
     * on its fetch() calls to the same `update`/`remove` endpoints the
     * plain `<form>`s already post to — a browser's own form submission
     * never sends that, so this alone is enough to pick the response
     * format without a second pair of routes.
     */
    private function wantsJson(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine(Header::ACCEPT), 'application/json');
    }

    private function jsonCartResponse(int $productId, bool $clamped = false): ResponseInterface
    {
        $item = null;
        foreach ($this->cartService->getItems() as $candidate) {
            if ($candidate->productId === $productId) {
                $item = $candidate;
                break;
            }
        }

        // subtotalFormatted/totalFormatted go through the same
        // CurrencyContext::format() the server-rendered page uses —
        // cart.ts writes these into the DOM directly rather than
        // formatting the raw subtotal/total itself, so there's exactly
        // one place currency symbols/conversion ever happen, not two
        // (PHP here, TypeScript there) that could drift apart.
        return $this->jsonResponseFactory->createResponse([
            'productId' => $productId,
            'quantity' => $item?->quantity,
            'subtotal' => $item?->subtotal(),
            'subtotalFormatted' => $item !== null ? $this->currency->format($item->subtotal()) : null,
            'removed' => $item === null,
            // True when the requested quantity exceeded availableStock and
            // was reduced to fit — cart.ts surfaces this as an inline
            // notice rather than silently applying a different number
            // than what the customer typed.
            'clamped' => $clamped,
            'total' => $this->cartService->getTotal(),
            'totalFormatted' => $this->currency->format($this->cartService->getTotal()),
            'count' => count($this->cartService->getItems()),
        ]);
    }
}
