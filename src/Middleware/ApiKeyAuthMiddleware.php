<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Api\ApiClientRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\DataResponse\ResponseFactory\DataResponseFactoryInterface;

/**
 * Gates external-partner `/api` routes — e.g. the future headless webshop's
 * `GET /api/products` and `POST /api/orders`, see
 * `docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md` — behind a
 * static key sent as the `X-Api-Key` header. This is deliberately separate
 * from `RoutePermission::check()`, the session/cookie RBAC gate every other
 * `/api` route (api/user/*) relies on today: a separately-deployed external
 * app has no browser session to hold, so it needs its own credential.
 *
 * Keys are minted via `yii api-client/generate`
 * (`App\Api\Console\GenerateApiKeyCommand`) and stored on `ApiClient` only
 * as a sha256 hash — the plaintext key is shown exactly once, at
 * generation time, and is not recoverable afterwards. A compromised key is
 * revoked by disabling its `ApiClient` row (`enabled = false`), not by
 * rotating an app-wide secret shared by every caller.
 *
 * Applied per-route (matching how `RoutePermission::check()` is applied
 * per-route within the existing `/api` group in routes.php), not to the
 * whole `/api` group — the existing session-authenticated routes
 * (`api/info/*`, `api/user/*`) are untouched by this middleware.
 */
final class ApiKeyAuthMiddleware implements MiddlewareInterface
{
    private const HEADER = 'X-Api-Key';

    public function __construct(
        private readonly ApiClientRepository $apiClientRepository,
        private readonly DataResponseFactoryInterface $responseFactory,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $key = trim($request->getHeaderLine(self::HEADER));
        if ($key === '') {
            return $this->reject($request, 'Missing X-Api-Key header.');
        }

        $client = $this->apiClientRepository->findActiveByKeyHash(hash('sha256', $key));
        if ($client === null) {
            return $this->reject($request, 'Invalid API key.');
        }

        $client->touchLastUsed();
        $this->apiClientRepository->save($client);

        return $handler->handle($request->withAttribute('apiClient', $client));
    }

    private function reject(ServerRequestInterface $request, string $message): ResponseInterface
    {
        $this->logger->warning('Rejected /api request: ' . $message, [
            'path' => $request->getUri()->getPath(),
        ]);

        return $this->responseFactory->createResponse($message, 401);
    }
}
