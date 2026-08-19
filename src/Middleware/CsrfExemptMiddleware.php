<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Csrf\CsrfTokenMiddleware;

/**
 * Decorates CsrfTokenMiddleware so specific paths — external callers that
 * can never carry an app CSRF token, such as provider webhooks — skip CSRF
 * validation entirely while every other request is checked exactly as
 * before.
 */
final class CsrfExemptMiddleware implements MiddlewareInterface
{
    private const EXEMPT_PATH_SUBSTRINGS = [
        '/paymentinformation/stripeWebhook',
        '/paymentinformation/adyenWebhook',
        '/paymentinformation/goCardlessWebhook',
        '/paymentinformation/robokassaWebhook',
        '/paymentinformation/yookassaWebhook',
        '/paymentinformation/mollieWebhook',
        '/paymentinformation/paystackWebhook',
        '/paymentinformation/razorpayWebhook',
        '/paymentinformation/mercadoPagoWebhook',
        '/paymentinformation/paypalWebhook',
        '/paymentinformation/squareWebhook',
        '/paymentinformation/checkoutComWebhook',
        '/paymentinformation/trueLayerWebhook',
        '/whatsapp/webhook',
        '/telegram/webhook',
        // External-partner API, gated by ApiKeyAuthMiddleware instead of a
        // browser session — a separately-deployed caller (e.g. the future
        // webshop storefront) has no CSRF token to send. See
        // docs/WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md.
        '/api/orders',
    ];

    public function __construct(
        private readonly CsrfTokenMiddleware $inner,
    ) {
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        foreach (self::EXEMPT_PATH_SUBSTRINGS as $exempt) {
            if (str_contains($path, $exempt)) {
                return $handler->handle($request);
            }
        }

        return $this->inner->process($request, $handler);
    }
}
