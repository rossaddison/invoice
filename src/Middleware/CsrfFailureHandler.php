<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\WebControllerService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Replaces Yiisoft\Csrf\CsrfTokenMiddleware's own default failure
 * response with a redirect to site/index.
 *
 * CsrfTokenMiddleware's default (no $failureHandler configured) is a bare
 * 422 with no Content-Type set -- literally
 * Status::TEXTS[Status::UNPROCESSABLE_ENTITY] ("Unprocessable Entity")
 * written into an otherwise-empty body, rendered by every browser as
 * unstyled plain text stuck in the corner of the page. Confirmed live:
 * clicking Logout after leaving a page open long enough for the session
 * (and the CSRF token embedded in it) to go stale lands here every time.
 *
 * Wired directly as CsrfTokenMiddleware's own $failureHandler (see
 * config/common/di/router.php), not as a wrapping middleware that sniffs
 * response status codes after the fact -- that alternative was tried
 * first and rejected: a wrapper watching for a bare 422 anywhere
 * downstream can't tell "CSRF rejected this request" apart from some
 * unrelated controller's own legitimate 422 (e.g.
 * SalesOrderOrderResponseTrait::previewOrderResponse() returns a real 422
 * on As4OrderResponseException) -- it would silently swallow that into a
 * redirect too. Wiring this as CsrfTokenMiddleware's actual
 * $failureHandler fires only at CSRF's own genuine point of failure, so
 * this class needs no method/status-code guards of its own -- the library
 * itself already only invokes it there (and never at all for GET/HEAD/
 * OPTIONS, which skip CSRF validation entirely).
 *
 * Redirects to site/index rather than back to the same URI (the more
 * "resume where you were" approach RateLimitRedirectMiddleware takes for
 * 429s -- see that class's own docblock): unlike the rate-limited routes
 * (login, signup, ... -- each a GET+POST pair on one URI, so replaying
 * the same URI as a GET naturally re-renders the form with a fresh
 * token), a CSRF failure can happen on ANY unsafe-method route in the
 * app -- including auth/logout, which is POST-only with no matching GET,
 * where replaying the same URI would 404 instead of recovering.
 * site/index works uniformly for every route without needing to know
 * which ones have a GET counterpart.
 */
final class CsrfFailureHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly WebControllerService $webService,
    ) {
    }

    #[\Override]
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->webService->getRedirectResponse('site/index');
    }
}
