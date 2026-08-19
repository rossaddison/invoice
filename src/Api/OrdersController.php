<?php

declare(strict_types=1);

namespace App\Api;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Guest checkout endpoint for external partners (e.g. the future headless
 * webshop storefront — see
 * docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md), gated by
 * ApiKeyAuthMiddleware. No `BodyParserMiddleware` is registered anywhere in
 * this app (confirmed — every other JSON-consuming handler here, e.g.
 * StripeWebhookHandler, reads the raw stream itself), so the JSON body is
 * decoded directly rather than via `getParsedBody()`.
 */
final readonly class OrdersController
{
    public function __construct(private DataResponseFactoryInterface $responseFactory)
    {
    }

    public function create(ServerRequestInterface $request, OrderService $orderService): ResponseInterface
    {
        $parsed = $this->parsePayload($request);
        if ($parsed instanceof ResponseInterface) {
            return $parsed;
        }

        $urlKey = $orderService->createOrder($parsed['customer'], $parsed['items']);
        if ($urlKey === null) {
            return $this->responseFactory->createResponse('Could not create order', 422);
        }

        return $this->responseFactory->createResponse(['url_key' => $urlKey], 201);
    }

    /**
     * @return array{customer: array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string},
     *     items: list<array{product_id: int, quantity: float}>}|ResponseInterface
     */
    private function parsePayload(ServerRequestInterface $request): array|ResponseInterface
    {
        /** @var mixed $payload */
        $payload = json_decode($request->getBody()->getContents(), true);
        if (!is_array($payload)) {
            return $this->responseFactory->createResponse('Invalid JSON body', 400);
        }

        $customer = $this->extractCustomer($payload);
        $items = $this->extractItems($payload);
        if ($customer === null || $items === null) {
            return $this->responseFactory->createResponse(
                'Expected a "customer" object (name, surname, email) and a non-empty "items" array (product_id, quantity)',
                422,
            );
        }

        return ['customer' => $customer, 'items' => $items];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return array{name: string, surname: string, email: string, address1?: string,
     *     address2?: string, city?: string, zip?: string, country?: string, phone?: string}|null
     */
    private function extractCustomer(array $payload): ?array
    {
        /** @var mixed $raw */
        $raw = $payload['customer'] ?? null;
        if (!is_array($raw)) {
            return null;
        }

        $email = is_string($raw['email'] ?? null) ? trim((string) $raw['email']) : '';
        if ($email === '' || !str_contains($email, '@')) {
            return null;
        }

        return [
            'name' => is_string($raw['name'] ?? null) ? (string) $raw['name'] : '',
            'surname' => is_string($raw['surname'] ?? null) ? (string) $raw['surname'] : '',
            'email' => $email,
            'address1' => is_string($raw['address1'] ?? null) ? (string) $raw['address1'] : '',
            'address2' => is_string($raw['address2'] ?? null) ? (string) $raw['address2'] : '',
            'city' => is_string($raw['city'] ?? null) ? (string) $raw['city'] : '',
            'zip' => is_string($raw['zip'] ?? null) ? (string) $raw['zip'] : '',
            'country' => is_string($raw['country'] ?? null) ? (string) $raw['country'] : '',
            'phone' => is_string($raw['phone'] ?? null) ? (string) $raw['phone'] : '',
        ];
    }

    /**
     * @param array<array-key, mixed> $payload
     * @return list<array{product_id: int, quantity: float}>|null
     */
    private function extractItems(array $payload): ?array
    {
        /** @var mixed $rawItems */
        $rawItems = $payload['items'] ?? null;
        if (!is_array($rawItems) || $rawItems === []) {
            return null;
        }

        $items = [];
        /** @var mixed $rawItem */
        foreach ($rawItems as $rawItem) {
            $item = $this->extractItem($rawItem);
            if ($item === null) {
                return null;
            }
            $items[] = $item;
        }

        return $items;
    }

    /** @return array{product_id: int, quantity: float}|null */
    private function extractItem(mixed $rawItem): ?array
    {
        if (!is_array($rawItem)) {
            return null;
        }
        $productId = (int) ($rawItem['product_id'] ?? 0);
        $quantity = (float) ($rawItem['quantity'] ?? 0);
        if ($productId <= 0 || $quantity <= 0.0) {
            return null;
        }
        return ['product_id' => $productId, 'quantity' => $quantity];
    }
}
