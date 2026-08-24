<?php

declare(strict_types=1);

namespace App\Webshop\Cart;

use App\Service\WebControllerService;
use App\Webshop\Catalog\CatalogQueryService;
use App\Webshop\Controller\StorefrontController;
use App\Webshop\Currency\CurrencyContext;
use App\Webshop\StorefrontViewParameters;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Header;
use Yiisoft\Router\HydratorAttribute\RouteArgument;
use Yiisoft\Yii\View\Renderer\WebViewRenderer;

final class CartController extends StorefrontController
{
    public function __construct(
        WebViewRenderer $webViewRenderer,
        private readonly WebControllerService $webService,
        private readonly CartService $cartService,
        private readonly JsonResponseFactory $jsonResponseFactory,
        private readonly CurrencyContext $currency,
        private readonly StorefrontViewParameters $chrome,
    ) {
        parent::__construct($webViewRenderer, 'shop/cart');
    }

    public function index(): ResponseInterface
    {
        return $this->render('index', [
            ...$this->chrome->getLayoutParameters(),
            'items' => $this->cartService->getItems(),
            'total' => $this->cartService->getTotal(),
        ]);
    }

    /**
     * Re-resolves name/price from the catalog rather than trusting the
     * submitted form — the same "catalog is the only source of truth for
     * price" principle `App\Api\OrderService` already enforces for the
     * cart-to-order handoff.
     */
    public function add(ServerRequestInterface $request, CatalogQueryService $catalog): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 1);

        $product = $catalog->find($productId);
        if ($product !== null && $quantity > 0.0) {
            $this->cartService->add($product->id, $product->displayName(), $product->price, $quantity);
        }

        return $this->webService->getRedirectResponse('shop/cart/index');
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (float) ($body['quantity'] ?? 0);

        $this->cartService->updateQuantity($productId, $quantity);

        return $this->wantsJson($request)
            ? $this->jsonCartResponse($productId)
            : $this->webService->getRedirectResponse('shop/cart/index');
    }

    public function remove(ServerRequestInterface $request, #[RouteArgument('id')] int $id): ResponseInterface
    {
        $this->cartService->remove($id);

        return $this->wantsJson($request)
            ? $this->jsonCartResponse($id)
            : $this->webService->getRedirectResponse('shop/cart/index');
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

    private function jsonCartResponse(int $productId): ResponseInterface
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
            'total' => $this->cartService->getTotal(),
            'totalFormatted' => $this->currency->format($this->cartService->getTotal()),
            'count' => count($this->cartService->getItems()),
        ]);
    }
}
