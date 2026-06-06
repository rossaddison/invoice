<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Adds a Content-Security-Policy header to every response.
 *
 * The policy string is injected via DI from config/web/params.php so that
 * payment-provider domains (Stripe, Braintree, Mollie, Amazon Pay) can be
 * appended without touching this class.
 *
 * Directives summary:
 *   default-src 'self'           — block everything external by default
 *   script-src  'self' + extras  — only our IIFE bundle + opt-in payment SDKs
 *   style-src   'self' 'unsafe-inline' — Bootstrap 5 injects inline styles
 *   img-src     'self' data: blob:    — data: for QR codes / base64 charts
 *   font-src    'self' data:          — embedded web-fonts
 *   connect-src 'self'               — htmx AJAX stays on-origin
 *   form-action 'self'               — forms may not POST off-site
 *   frame-ancestors 'none'           — clickjacking prevention
 *   base-uri    'self'               — block <base> tag injection
 *   object-src  'none'               — no plugins (Flash etc.)
 */
final class ContentSecurityPolicyMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $policy) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        return $handler->handle($request)
            ->withHeader('Content-Security-Policy', $this->policy);
    }
}
