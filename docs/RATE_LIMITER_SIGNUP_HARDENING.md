# Rate Limiter & Signup — Bot-Wave Hardening (July 2026)

## Background

Following a 900-bot wave against the `/signup` route in July 2026, four structural
fixes were implemented across three files. Each fix maps to a numbered limitation in
[`docs/RATE_LIMITER_SIGNUP_LIMITATIONS.md`](RATE_LIMITER_SIGNUP_LIMITATIONS.md).

---

## Fix #1 — Fingerprint using CF-Connecting-IP rather than REMOTE_ADDR (Critical)

**Problem:** `LimitPerIp` built its storage key from `REMOTE_ADDR`, which is
Cloudflare's edge IP when the site sits behind Cloudflare's proxy. Every visitor
and every bot shared the same per-IP GCRA bucket.

**Implementation** — `config/common/routes/routes.php`

Replaced the default `LimitPerIp` policy with a `LimitCallback` that reads the
client IP supplied by Cloudflare (`CF-Connecting-IP`) rather than the proxy's
`REMOTE_ADDR`, falling back through `X-Forwarded-For` and then `REMOTE_ADDR` for
non-Cloudflare environments:

```php
new LimitCallback(static function (ServerRequestInterface $r): string {
    $srv  = $r->getServerParams();
    $cfIp = isset($srv['HTTP_CF_CONNECTING_IP']) ? (string) $srv['HTTP_CF_CONNECTING_IP'] : '';
    $xfw  = isset($srv['HTTP_X_FORWARDED_FOR'])  ? (string) $srv['HTTP_X_FORWARDED_FOR']  : '';
    $rem  = isset($srv['REMOTE_ADDR'])            ? (string) $srv['REMOTE_ADDR']            : '';
    $ip   = $cfIp !== '' ? $cfIp : ($xfw !== '' ? $xfw : ($rem !== '' ? $rem : 'unknown'));
    return sha1('signup' . $ip);
}),
```

Each visitor IP now gets its own isolated GCRA bucket regardless of how many
Cloudflare edge nodes are in the path.

> **Deployment assumption:** this relies on Cloudflare being the only path to the
> origin. Direct origin access must be blocked at the firewall level so that
> requests cannot spoof `CF-Connecting-IP`.

---

## Fix #2 — Global path counter against botnets (High)

**Problem:** With a 900-IP botnet and a limit of 10/10 s, up to 9,000 requests per
10-second window reach the application before any individual IP bucket triggers.
A per-IP limiter alone is insufficient when every bot arrives from a distinct IP.

**Implementation** — `config/common/routes/routes.php`

A second `LimitRequestsMiddleware` is layered in front of the per-IP middleware
using `LimitAlways` (a policy that fingerprints by path only, shared across all IPs):

```php
Route::methods([$mG, $mP], '/signup')
    // Outer: 50 total POSTs per 10 s on /signup, regardless of IP
    ->middleware(fn(...) => new LRM(
        new Counter($storage, 50, 10),
        $responseFactory,
        new LimitAlways(),
        new TooManyRequestsMiddleware($responseFactory),
    ))
    // Inner: 5 per 60 s per real IP
    ->middleware(fn(...) => new LRM(
        new Counter($storage, 5, 60),
        $responseFactory,
        $cfIpPolicy,
        new TooManyRequestsMiddleware($responseFactory),
    ))
    ->action([SignupController::class, 'signup'])
    ->name('auth/signup'),
```

Once the shared counter reaches capacity, subsequent requests are rejected before
the per-IP limiter executes.

---

## Fix #3 — CAS storage failure returns 429 (High)

**Problem:** Under high concurrency, `FileCache` experiences lock contention.
`Counter` retries its compare-and-swap (CAS) operation up to `maxCasAttempts`
(default 10) times, then sets `isFailStoreUpdatedData = true`. Because no
`failStoreUpdatedDataMiddleware` was supplied to `LimitRequestsMiddleware`, the
constructor stored `null` and the middleware treated the storage failure as
non-fatal and forwarded the request to the next handler.

**Implementation** — `src/Middleware/TooManyRequestsMiddleware.php` (new class)

```php
final class TooManyRequestsMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responseFactory) {}

    #[\Override]
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $this->responseFactory->createResponse(Status::TOO_MANY_REQUESTS);
        $response->getBody()->write(Status::TEXTS[Status::TOO_MANY_REQUESTS]);
        return $response;
    }
}
```

Both the outer (`LimitAlways`) and inner (`LimitCallback`) middlewares now pass
`new TooManyRequestsMiddleware($responseFactory)` as the fourth argument. Any CAS
contention during a bot wave returns 429 rather than silently admitting the request.

---

## Fix #4 — Turnstile verified on every POST before form hydration (High)

**Problem:** `verifyTurnstile()` was called inside
`if ($formHydrator->populateFromPostAndValidate(...))`. An attacker could bypass
Turnstile verification entirely by submitting a malformed POST body — missing
required fields would cause form validation to fail before Turnstile was ever
called, yielding a full server-rendered HTML response with no CAPTCHA check
performed.

**Implementation** — `src/Auth/Controller/SignupController.php`

The Turnstile check is now the first thing that runs on every POST, using
`CF-Connecting-IP` for the real client IP, before form hydration or validation
occurs:

```php
if ($request->getMethod() === 'POST') {
    $body = (array) $request->getParsedBody();
    $srv  = $request->getServerParams();
    $ip   = (string) ($srv['HTTP_CF_CONNECTING_IP'] ?? $srv['REMOTE_ADDR'] ?? '');
    if (!$this->verifyTurnstile((string) ($body['cf-turnstile-response'] ?? ''), $ip)) {
        return $this->webService->getRedirectResponse('site/signupfailed');
    }
}
// form hydration and account creation follow here
```

This prevents attackers from bypassing Turnstile verification simply by submitting
malformed payloads that fail validation early. A request with no valid Turnstile
token is rejected before form hydration or validation occurs.

---

## Summary of changes

| Fix | Severity | File(s) changed |
|-----|----------|-----------------|
| #1 — `LimitCallback` reading `CF-Connecting-IP` | Critical | `config/common/routes/routes.php` |
| #2 — `LimitAlways` global path counter (50/10 s) | High | `config/common/routes/routes.php` |
| #3 — `TooManyRequestsMiddleware` for CAS failures | High | `src/Middleware/TooManyRequestsMiddleware.php` (new), `config/common/routes/routes.php` |
| #4 — Turnstile before form hydration, uses real IP | High | `src/Auth/Controller/SignupController.php` |

Fixes #5 (strip rate-limit headers from 429) and #6 (method-agnostic fingerprint)
remain low-priority and are documented in
[`docs/RATE_LIMITER_SIGNUP_LIMITATIONS.md`](RATE_LIMITER_SIGNUP_LIMITATIONS.md)
for a future PR.

Psalm errorLevel 1 clean across all three files (July 2026).
