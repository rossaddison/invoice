<?php

declare(strict_types=1);

namespace App\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Header;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

/**
 * Wraps a rate-limiting middleware (LimitRequestsMiddleware, or
 * TooManyRequestsMiddleware's own CAS-failure path — both return a bare
 * 429 with no Content-Type set, rendered by every browser as unstyled
 * black-on-white text) and turns a 429 into a plain redirect back to the
 * page the request was for instead.
 *
 * Mirrors AuthController::login()'s own controller-level rate-limit check
 * (AuthSecurityHelper::checkRateLimit()/checkAccountRateLimit()), which
 * already does exactly this — redirect back, no error message — for its
 * own, separate rate-limit layer. This applies the same behaviour to the
 * outer PSR-15 middleware layer (RateLimiter::global()/perIp()), which
 * runs before any controller and so has no form/session context to build
 * a proper validation-style error message the way the controller-level
 * check can.
 *
 * Redirects to the exact request URI rather than a named route, so this
 * works unmodified for every route it wraps (login, forgotpassword,
 * resetpassword, signup, ...) without per-route configuration. A 302 to
 * a POST is followed as a GET by every browser, so this is a normal
 * Post/Redirect/Get bounce back to the same form, not a resubmission.
 *
 * Safe/idempotent methods (GET, HEAD, OPTIONS) never reach the inner
 * limiter at all — confirmed live that leaving them counted (as the
 * wrapped LimitRequestsMiddleware does by default, sharing one counter
 * across every method Route::methods() registers) is actively dangerous
 * once 429s redirect: a plain page view that itself trips the limit would
 * redirect back to that exact same GET, which is still over budget for
 * the rest of the window, producing an infinite redirect loop
 * (ERR_TOO_MANY_REDIRECTS) rather than the intended one-time bounce.
 * Mirrors AuthController::login()'s own controller-level rate-limit
 * check, which already only ever throttles POST for the same reason.
 */
final class RateLimitRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly MiddlewareInterface $inner,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (in_array($request->getMethod(), [Method::GET, Method::HEAD, Method::OPTIONS], true)) {
            return $handler->handle($request);
        }

        $response = $this->inner->process($request, $handler);

        if ($response->getStatusCode() !== Status::TOO_MANY_REQUESTS) {
            return $response;
        }

        return $this->responseFactory
            ->createResponse(Status::FOUND)
            ->withHeader(Header::LOCATION, (string) $request->getUri());
    }
}
