# Rate Limit "Too Many Requests" — Bare 429 to Clean Redirect — August 2026

## Summary

Fixed a real, user-reported bug: hitting the login rate limiter showed a
raw, unstyled "Too Many Requests" page — black text on white, browser
default serif font, no layout — instead of anything resembling the rest
of the app. Traced to the exact line in the vendor rate-limiter package
producing it, fixed with a small wrapping middleware, then caught and
fixed a second, more serious regression the fix itself introduced,
found only by testing live against the real running app.

## The bug

`vendor/yiisoft/rate-limiter/src/LimitRequestsMiddleware.php`'s own
`createErrorResponse()`:

```php
$response = $this->responseFactory->createResponse(Status::TOO_MANY_REQUESTS);
$response->getBody()->write(Status::TEXTS[Status::TOO_MANY_REQUESTS]);
```

No `Content-Type` header, no HTML — just the literal string "Too Many
Requests" written into the body. Every browser renders that with its
own default stylesheet: unstyled serif text, top-left, white background
— exactly what was reported. This app's own `TooManyRequestsMiddleware`
(used as `LimitRequestsMiddleware`'s CAS-failure fallback) does the
identical thing for its own, narrower case.

## The fix

New `App\Middleware\RateLimitRedirectMiddleware` wraps the limiter and
turns a 429 into a plain 302 back to the exact request URI instead —
redirecting to the request's own URI rather than a named route means it
works unmodified on every route it wraps (login, forgotpassword,
resetpassword, signup, change — anything built with the shared
`RateLimiter::global()`/`perIp()` builders in `src/Middleware/RateLimiter.php`)
with no per-route configuration.

This mirrors a pattern that already existed in the app: `AuthController::login()`
has its own, separate, controller-level rate-limit check
(`AuthSecurityHelper::checkRateLimit()`/`checkAccountRateLimit()`) that
already redirects back to the login form with no error message when it
trips — the new middleware brings the outer, PSR-15-layer limiter's
behaviour in line with what the inner, controller-layer one already did.

## A second bug the fix itself exposed

Live testing against the real running app (not just the unit tests)
caught a real regression: `RateLimiter::perIp(5, 'login_route')` is
attached to `/login`'s whole route (`Route::methods([$mG, $mP], '/login')`),
so GET page-loads and POST submissions shared *one* counter. Once 429s
started redirecting, a GET request that itself tripped the limit would
redirect back to that exact same, still-over-budget GET — looping for
the rest of the rate-limit window. Reproduced live as a genuine
`ERR_TOO_MANY_REDIRECTS` in a real browser session.

Fixed by having `RateLimitRedirectMiddleware` skip the inner limiter
entirely for safe/idempotent methods (GET, HEAD, OPTIONS) — only
state-changing requests are ever counted. This matches
`AuthController::login()`'s own controller-level check, which was
already scoped to POST only for the same reason; the middleware-level
limiter simply hadn't matched that scoping until now.

## Verification

- `php -l` clean on every new/changed file.
- Full-project Psalm (`vendor/bin/psalm --no-cache`): no errors found.
- Full Testo Unit suite: 833/833 passing (5 new tests in
  `RateLimitRedirectMiddlewareTest`, including one that reproduces the
  live GET-loop regression against a stubbed always-429 inner limiter —
  it would fail immediately if that guard ever regressed).
- Full PHPUnit Unit suite: 3,854/3,854 passing.
- Live-confirmed end-to-end via Playwright against the real running app:
  5 rapid POSTs to `/login` return `200/200/200/200/302` — the 5th
  correctly trips the configured `perIp(5, ...)` limit and redirects
  cleanly, with no redirect loop across 10 repeated attempts.
