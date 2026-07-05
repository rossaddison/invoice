# Rate Limiter & Signup Bot-Protection — Known Limitations and Fixes

## Background

The `/signup` route uses `Yiisoft\Yii\RateLimiter\LimitRequestsMiddleware` (GCRA
algorithm) backed by `FileCache`. In July 2026 the signup endpoint received a
wave of ~900 bot requests. The analysis below identifies six structural
limitations in the current setup and the recommended fix for each, suitable for
a PR.

---

## 1. `REMOTE_ADDR` is wrong behind Cloudflare — Critical

`LimitPerIp::fingerprint()` builds its storage key from:

```php
sha1($method . $path . $server['REMOTE_ADDR'])
```

When traffic flows through Cloudflare's reverse proxy, `REMOTE_ADDR` is
**Cloudflare's edge IP**, not the visitor's real IP. Every bot appears to arrive
from the same handful of Cloudflare egress addresses, so the per-IP bucket is
shared across all visitors and all bots simultaneously.

The real client IP is forwarded in `CF-Connecting-IP` (Cloudflare) or
`X-Forwarded-For` (generic proxy). Neither is read by `LimitPerIp`.

**Fix** — replace the default policy with a `LimitCallback` that reads the
correct header chain:

```php
use Yiisoft\Yii\RateLimiter\Policy\LimitCallback;

->middleware(fn(
    ResponseFactoryInterface $f,
    StorageInterface $s,
) => new LRM(
    new Counter($s, 5, 60),   // 5 attempts per minute per real IP
    $f,
    new LimitCallback(static function (ServerRequestInterface $r): string {
        $srv = $r->getServerParams();
        $ip  = $srv['HTTP_CF_CONNECTING_IP']
            ?? $srv['HTTP_X_FORWARDED_FOR']
            ?? $srv['REMOTE_ADDR']
            ?? '';
        return sha1('POST/signup' . $ip);
    }),
))
```

---

## 2. Per-IP limit is useless against a botnet — High

Even with the correct IP, each distinct bot IP gets its own isolated bucket.
With the current limit of 10 requests / 10 seconds and a 900-IP botnet, up to
**9,000 requests** can reach the application in a single 10-second window before
any individual IP is throttled.

**Fix** — layer a second, stricter `LimitAlways` (path-scoped, shared across all
IPs) in front of the per-IP middleware:

```php
use Yiisoft\Yii\RateLimiter\Policy\LimitAlways;

// Outer: 50 total POST requests per 10 s on /signup, regardless of IP
->middleware(fn(...) => new LRM(new Counter($s, 50, 10), $f, new LimitAlways()))
// Inner: per real-IP
->middleware(fn(...) => new LRM(new Counter($s, 3, 60), $f, $cfIpPolicy))
```

---

## 3. CAS storage failure silently allows requests through — High

Under high concurrency (900 concurrent writes), `FileCache` experiences lock
contention. `Counter` retries its compare-and-swap (CAS) operation up to
`maxCasAttempts` (default 10) times, then sets `isFailStoreUpdatedData = true`.

Because the `/signup` route passes **no `failStoreUpdatedDataMiddleware`** to
`LimitRequestsMiddleware`, the constructor stores `null` and the middleware falls
into the `else` branch — passing the request to the handler unthrottled:

```php
// LimitRequestsMiddleware::process()
} elseif ($state->isFailStoreUpdatedData() && $this->failStoreUpdatedDataMiddleware !== null) {
    // ← null, so this branch is skipped entirely
} else {
    $response = $handler->handle($request); // ← bot is allowed through
}
```

**Fix (option A)** — pass an explicit fail middleware that returns 429:

```php
new LRM(
    $counter,
    $responseFactory,
    $policy,
    new class($responseFactory) implements MiddlewareInterface {
        public function __construct(
            private ResponseFactoryInterface $f
        ) {}
        public function process(
            ServerRequestInterface $req,
            RequestHandlerInterface $handler
        ): ResponseInterface {
            $r = $this->f->createResponse(Status::TOO_MANY_REQUESTS);
            $r->getBody()->write('Too Many Requests');
            return $r;
        }
    }
)
```

**Fix (option B)** — switch `StorageInterface` from `FileCache` to `APCuCache`
(already wired in `config/common/di/cache.php`). APCu uses atomic operations,
eliminating CAS contention entirely.

---

## 4. Turnstile fires only after form validation passes — Medium

In `SignupController::signup()` the Cloudflare Turnstile token is verified
**inside** the `if ($formHydrator->populateFromPostAndValidate(...))` block:

```php
if ($formHydrator->populateFromPostAndValidate($signupForm, $request)) {
    // Turnstile verified here — but only if the form is structurally valid
    if (!$this->verifyTurnstile($token, $ip)) { ... }
}
```

A bot that sends an intentionally malformed POST body (missing fields, wrong
field types) fails form validation before Turnstile is ever called. The bot
receives a full server-rendered HTML response at zero Turnstile cost.

**Fix** — move `verifyTurnstile()` before form hydration so it fires on every
POST, regardless of form validity:

```php
if ($request->getMethod() === 'POST') {
    $body = (array) $request->getParsedBody();
    $srv  = $request->getServerParams();
    $ip   = (string) ($srv['HTTP_CF_CONNECTING_IP'] ?? $srv['REMOTE_ADDR'] ?? '');
    if (!$this->verifyTurnstile(
        (string) ($body['cf-turnstile-response'] ?? ''),
        $ip,
    )) {
        return $this->webService->getRedirectResponse('site/signupfailed');
    }
}
```

---

## 5. Rate limit headers advertise the reset window to bots — Low

`LimitRequestsMiddleware::addHeaders()` appends these headers to **every**
response, including 429s:

```
X-Rate-Limit-Limit:     10
X-Rate-Limit-Remaining: 0
X-Rate-Limit-Reset:     <unix timestamp>
```

Sophisticated bots read `X-Rate-Limit-Reset` and schedule their next request
burst for exactly that moment.

**Fix** — strip these headers from 429 responses in production, or remove the
`addHeaders()` call from the 429 path inside a subclass / decorator.

---

## 6. GET and POST are rate-limited in separate buckets — Low

`LimitPerIp` includes the HTTP method in its fingerprint:

```php
sha1(strtolower($request->getMethod() . $request->getUri()->getPath() . $ip))
```

A single IP can exhaust its GET bucket (10 requests = page loads) and its POST
bucket (10 requests = submission attempts) independently — effectively 20
attempts per window before any single bucket triggers.

**Fix** — use a method-agnostic fingerprint for the signup route, limiting only
POST (where account creation happens), or combine both methods into one shared
counter key.

---

## Summary

| # | Issue | Severity | Recommended fix |
|---|-------|----------|-----------------|
| 1 | `REMOTE_ADDR` wrong behind Cloudflare | **Critical** | `LimitCallback` reading `CF-Connecting-IP` |
| 2 | Per-IP limit useless against botnets | **High** | Add a global `LimitAlways` path counter |
| 3 | CAS failure silently allows requests | **High** | Pass fail-middleware or switch to APCu storage |
| 4 | Turnstile fires after form validation | **Medium** | Move Turnstile check before hydration |
| 5 | Rate headers teach bots the reset time | **Low** | Strip from 429 responses |
| 6 | GET/POST counted in separate buckets | **Low** | Method-agnostic fingerprint on signup |

---

## Related files

- `config/web/di/rate-limit.php` — DI bindings for `CounterInterface` and `StorageInterface`
- `config/common/routes/routes.php` — `/signup` route with inline `Counter(storage, 10, 10)`
- `src/Auth/Controller/SignupController.php` — `verifyTurnstile()` and `signup()`
- `resources/views/signup/signup.php` — Turnstile widget JS and hidden input
- `resources/views/invoice/setting/views/partial_settings_turnstile.php` — admin settings for site/secret keys
