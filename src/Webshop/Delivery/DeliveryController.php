<?php

declare(strict_types=1);

namespace App\Webshop\Delivery;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class DeliveryController
{
    public function __construct(
        private WebControllerService $webService,
        private DeliveryAddressService $deliveryAddressService,
    ) {
    }

    public function update(ServerRequestInterface $request): ResponseInterface
    {
        $body = (array) $request->getParsedBody();

        $this->deliveryAddressService->set(
            (string) ($body['name'] ?? ''),
            (string) ($body['surname'] ?? ''),
            (string) ($body['city'] ?? ''),
            (string) ($body['postcode'] ?? ''),
        );

        // The `redirect` field is a hidden input the storefront layout
        // fills in with `(string) $currentRoute->getUri()` (see
        // resources/views/layout/templates/storefront/main.php) — see
        // WebControllerService::getRedirectToSameOriginPathResponse()'s
        // own docblock for why it's still validated here.
        return $this->webService->getRedirectToSameOriginPathResponse((string) ($body['redirect'] ?? '/'));
    }
}
