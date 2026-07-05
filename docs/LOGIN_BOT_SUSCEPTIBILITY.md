# Login Route — Bot Susceptibility Analysis (July 2026)

## Background

Following the bot-wave hardening applied to `/signup` (see
[`docs/RATE_LIMITER_SIGNUP_HARDENING.md`](RATE_LIMITER_SIGNUP_HARDENING.md)), the
same structural audit was carried out on the `/login` route. The login endpoint
carries higher per-attempt value than signup — a successful credential guess yields
authenticated access — yet its current protection is weaker across every dimension.

---

## Current state

The `/login` route has a single middleware line:

```php
Route::methods([$mG, $mP], '/login')
    ->middleware(LRM::class)   // DI-injected LimitRequestsMiddleware
    ->action([AuthController::class, 'login'])
    ->name('auth/login'),
```

The DI-bound counter is defined in `config/web/di/rate-limit.php`:

```php
CounterInterface::class => [
    'class' => Counter::class,
    '__construct()' => [
        'limit' => 20, // Increased for test compatibility
        'periodInSeconds' => 10,
    ],
],
```

The limit was raised from 2 → 5 → 20 specifically to stop acceptance tests
triggering the limiter. That decision, while pragmatically necessary for CI, left
the login endpoint materially under-protected in production.

---

## Deficiency #1 — `REMOTE_ADDR` wrong behind Cloudflare (Critical)

`LRM::class` resolves to the DI-injected `LimitRequestsMiddleware` with its default
`LimitPerIp` policy, which fingerprints each bucket as:

```php
sha1(strtolower($request->getMethod() . $request->getUri()->getPath() . $ip))
// where $ip = $serverParams['REMOTE_ADDR']
```

When the site sits behind Cloudflare's reverse proxy, `REMOTE_ADDR` is Cloudflare's
edge IP — not the visitor's real IP. Every user and every bot on the planet shares
the same per-IP bucket against `/login`. One legitimate user exhausting 20 attempts
locks out all other users simultaneously.

The real client IP is carried in `CF-Connecting-IP` (Cloudflare) or
`X-Forwarded-For` (generic proxy). Neither is read by `LimitPerIp`.

**Deployment assumption that must hold:** direct origin access must be blocked at
the firewall level so that requests cannot spoof `CF-Connecting-IP`.

---

## Deficiency #2 — No global path counter against botnets (High)

With a 900-IP botnet and a per-IP limit of 20/10 s, up to **18,000 login attempts**
reach the application every 10 seconds before any individual IP bucket triggers.
A per-IP limiter alone is insufficient when every bot arrives from a distinct IP.

The signup route now layers a `LimitAlways` global counter in front of its per-IP
middleware. No equivalent exists on `/login`.

---

## Deficiency #3 — CAS storage failure silently allows requests through (High)

Under high concurrency, `FileCache` experiences lock contention. `Counter` retries
its compare-and-swap (CAS) operation up to `maxCasAttempts` (default 10) times,
then sets `isFailStoreUpdatedData = true`. Because no `failStoreUpdatedDataMiddleware`
is supplied to `LimitRequestsMiddleware` on the login route, the constructor stores
`null` and the middleware treats the storage failure as non-fatal, forwarding the
request to the next handler unthrottled.

`TooManyRequestsMiddleware` (introduced in the signup hardening) exists in
`src/Middleware/` and can be wired here immediately with no new code required.

---

## Deficiency #4 — No CAPTCHA on the login form (High)

The signup route has Cloudflare Turnstile, which is verified server-side before form
hydration. The login view (`resources/views/auth/login.php`) has no Turnstile widget
and no equivalent challenge. Automated credential-stuffing tools can submit
unlimited login attempts with no human-verification cost.

The Turnstile site key and secret key are already managed under Settings →
Cloudflare Turnstile, so adding the widget to the login view is a drop-in using the
same `verifyTurnstile()` pattern already present in `SignupController`.

---

## Deficiency #5 — Rate limit of 20/10 s is too permissive for a credential endpoint (High)

The 20-request-per-10-second limit was set for test-suite compatibility, not for
security. The former signup limit (10/10 s) was already considered insufficient
during the bot wave; the login limit is double that. A single IP can attempt 120
password guesses per minute before being throttled — an effective rate for both
targeted brute-force and low-and-slow credential stuffing.

The correct fix is to decouple the test-suite counter from the production login
counter, rather than raising the production limit to accommodate tests. The signup
route demonstrates the pattern: inline `Counter` instances on the route with their
own limits, independent of the DI-bound `CounterInterface`.

---

## Deficiency #6 — `CF-Connecting-IP` absent from `AuthSecurityHelper::getClientIpAddress()` (Medium)

`AuthSecurityHelper::getClientIpAddress()` iterates this header list:

```php
$ipHeaders = [
    'HTTP_X_FORWARDED_FOR',
    'HTTP_X_REAL_IP',
    'HTTP_CLIENT_IP',
    'REMOTE_ADDR',
];
```

`HTTP_CF_CONNECTING_IP` is not present. Even if `checkRateLimit()` were wired into
the login action (see Deficiency #7), it would still fingerprint by the wrong IP
behind Cloudflare.

---

## Deficiency #7 — `AuthSecurityHelper::checkRateLimit()` is never called from `login()` (Medium)

`AuthSecurityHelper` is constructed in `AuthController::__construct()` and stored as
`$this->secHelper`. It exposes `checkRateLimit(string $key): bool` backed by the
DI-injected `CounterInterface`. The method is never called inside `login()` or
`resolveLoginResponse()`. It exists as a defence-in-depth layer that is currently
disconnected from the action it was intended to protect.

---

## What partially mitigates the risk today

These existing controls reduce — but do not eliminate — the impact of a successful
bot wave:

- **2FA (when enabled):** a bot that guesses a password correctly still hits a TOTP
  challenge it cannot complete without the user's authenticator device.
- **`userInv->getActive()` check:** accounts that have not been activated through
  email verification cannot log in even with correct credentials. Brute-forcing an
  inactive account yields a dead end.
- **Session ID regeneration:** `$this->session->regenerateId()` is called on
  successful login, preventing session fixation.

These controls operate *after* authentication succeeds. They do not reduce the
volume of attempts reaching the application.

---

## Recommended fixes

| # | Fix | Severity | File(s) |
|---|-----|----------|---------|
| 1 | `LimitCallback` reading `CF-Connecting-IP` → `X-Forwarded-For` → `REMOTE_ADDR` | Critical | `config/common/routes/routes.php` |
| 2 | `LimitAlways` global path counter (e.g. 30/60 s) in front of per-IP layer | High | `config/common/routes/routes.php` |
| 3 | `TooManyRequestsMiddleware` as `failStoreUpdatedDataMiddleware` on both layers | High | `config/common/routes/routes.php` |
| 4 | Add Turnstile widget to login view; verify before form hydration | High | `resources/views/auth/login.php`, `AuthController` |
| 5 | Decouple test-suite counter from production login counter; tighten to 5/60 s | High | `config/common/routes/routes.php`, `config/web/di/rate-limit.php` |
| 6 | Add `HTTP_CF_CONNECTING_IP` as first entry in `getClientIpAddress()` | Medium | `src/Auth/Controller/AuthSecurityHelper.php` |
| 7 | Wire `$this->secHelper->checkRateLimit()` into `login()` as defence-in-depth | Medium | `src/Auth/Controller/AuthController.php` |

---

## Related files

- `config/common/routes/routes.php` — `/login` route with `->middleware(LRM::class)`
- `config/web/di/rate-limit.php` — DI-bound `Counter(20, 10)` shared by all `LRM::class` references
- `src/Auth/Controller/AuthController.php` — `login()` and `resolveLoginResponse()`
- `src/Auth/Controller/AuthSecurityHelper.php` — `checkRateLimit()`, `getClientIpAddress()`
- `src/Middleware/TooManyRequestsMiddleware.php` — CAS-failure 429 middleware (ready to wire)
- `resources/views/auth/login.php` — login form with no Turnstile widget
- `resources/views/invoice/setting/views/partial_settings_turnstile.php` — Turnstile key management
- [`docs/RATE_LIMITER_SIGNUP_HARDENING.md`](RATE_LIMITER_SIGNUP_HARDENING.md) — signup fixes already applied
- [`docs/RATE_LIMITER_SIGNUP_LIMITATIONS.md`](RATE_LIMITER_SIGNUP_LIMITATIONS.md) — original six-limitation analysis
