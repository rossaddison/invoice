<?php

declare(strict_types=1);

namespace App\Api;

use App\Infrastructure\Persistence\Product\Product;
use App\Invoice\Product\ProductRepository;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;
use Yiisoft\Router\CurrentRoute;

/**
 * Read-only product catalog feed for external partners (e.g. the future
 * headless webshop storefront — see
 * docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md), gated by
 * ApiKeyAuthMiddleware. Only products with a real price
 * (`ProductRepository::findAllPreloadedWithPrice()`, the same non-zero-price
 * filter this app's own invoice/quote product-lookup modals already use)
 * are exposed — zero-priced/internal-only catalog rows never appear here.
 */
final readonly class ProductsController
{
    public function __construct(private DataResponseFactoryInterface $responseFactory)
    {
    }

    public function index(ProductRepository $productRepository): ResponseInterface
    {
        $items = [];
        /** @var Product $product */
        foreach ($productRepository->findAllPreloadedWithPrice() as $product) {
            $items[] = $this->toArray($product);
        }

        return $this->responseFactory->createResponse($items);
    }

    public function show(ProductRepository $productRepository, CurrentRoute $currentRoute): ResponseInterface
    {
        $id = (int) $currentRoute->getArgument('id', '0');
        $product = $id > 0 ? $productRepository->repoProductquery($id) : null;

        if ($product === null || ($product->getProductPrice() ?? 0.00) <= 0.00) {
            return $this->responseFactory->createResponse('Product not found', 404);
        }

        return $this->responseFactory->createResponse($this->toArray($product));
    }

    /**
     * @return array{id: int, sku: ?string, name: ?string, description: ?string,
     *     price: float, unit: ?string}
     */
    private function toArray(Product $product): array
    {
        return [
            'id' => $product->reqId(),
            'sku' => $product->getProductSku(),
            'name' => $product->getProductName(),
            'description' => $product->getProductDescription(),
            'price' => $product->getProductPrice() ?? 0.00,
            'unit' => $product->getUnit()?->getUnitName(),
        ];
    }
}
