<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Http\Status;

/**
 * Wraps CsrfExemptMiddleware (itself decorating the real
 * Yiisoft\Csrf\CsrfTokenMiddleware) and turns a 422 into a redirect to
 * site/index instead.
 *
 * CsrfTokenMiddleware is never given a $failureHandler anywhere in this
 * app's config, so its own default failure response is a bare 422 with no
 * Content-Type set -- literally Status::TEXTS[Status::UNPROCESSABLE_ENTITY]
 * ("Unprocessable Entity") written into an otherwise-empty body, rendered
 * by every browser as unstyled plain text stuck in the corner of the page.
 * Confirmed live: clicking Logout after leaving a page open long enough for
 * the session (and the CSRF token embedded in it) to go stale lands here
 * every time -- the token the rendered page carried no longer matches what
 * the (now-expired-or-regenerated) session expects.
 *
 * Mirrors RateLimitRedirectMiddleware's already-established pattern for the
 * exact same "raw unstyled status code" problem, on 429s -- see that
 * class's own docblock. This redirects to site/index rather than back to
 * the same URI, though: unlike the rate-limited routes (login, signup, ...
 * -- each a GET+POST pair on one URI, so replaying the same URI as a GET
 * naturally re-renders the form with a fresh token), auth/logout is
 * POST-only with no matching GET, so replaying its own URI would 404
 * instead of recovering. site/index works for every route uniformly and
 * needs no per-route knowledge of which ones have a GET counterpart.
 */
final class CsrfRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CsrfExemptMiddleware $inner,
        private readonly WebControllerService $webService,
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

        if ($response->getStatusCode() !== Status::UNPROCESSABLE_ENTITY) {
            return $response;
        }

        return $this->webService->getRedirectResponse('site/index');
    }
}
