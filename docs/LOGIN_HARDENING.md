# Login Route — Hardening Implementation (July 2026)

This document records the seven fixes applied to the `/login` route after the
bot-susceptibility analysis in
[LOGIN_BOT_SUSCEPTIBILITY.md](LOGIN_BOT_SUSCEPTIBILITY.md).

---

## What Changed

| Fix | Description | File(s) |
|-----|-------------|---------|
| #1 | `LimitCallback` reads `CF-Connecting-IP` so each GCRA bucket fingerprints the **real client IP** behind Cloudflare, not the shared edge-proxy `REMOTE_ADDR` | `config/common/routes/routes.php` |
| #2 | `LimitAlways` global counter (30 POSTs / 60 s on `/login` regardless of IP) collapses the combined traffic of a distributed botnet into one shared bucket before any per-IP check runs | `config/common/routes/routes.php` |
| #3 | `TooManyRequestsMiddleware` registered as `failStoreUpdatedDataMiddleware` on **both** rate-limit layers so `FileCache` CAS contention returns 429 instead of silently forwarding the request | `config/common/routes/routes.php` |
| #4 | Turnstile widget injected into the login form; token verified in `AuthController::login()` **before** `FormHydrator::populateFromPostAndValidate()` runs, preventing malformed POST bodies from bypassing the challenge | `resources/views/auth/login.php`, `src/Auth/Controller/AuthController.php` |
| #5 | Inner per-IP `Counter(5, 60)` is a separate DI-constructed instance from the global `Counter(30, 60)` — the two share no state and the limit is no longer inflated to 20 for test-suite compatibility | `config/common/routes/routes.php` |
| #6 | `HTTP_CF_CONNECTING_IP` added as the **first** entry in `AuthSecurityHelper::getClientIpAddress()` header priority chain so the correct client IP is used for logging, threat detection, and in-controller rate-limit checks | `src/Auth/Controller/AuthSecurityHelper.php` |
| #7 | `AuthSecurityHelper::checkRateLimit()` is now **called from `login()`** with a distinct key prefix (`login_ctrl`) so the in-controller defence-in-depth layer is actually active (it was wired but never invoked) | `src/Auth/Controller/AuthController.php` |

---

## Shared Trait — `TurnstileVerification`

`verifyTurnstile()` has been extracted into
`src/Auth/Trait/TurnstileVerification.php` and used by both
`AuthController` and `SignupController`, eliminating the duplication that
SonarQube would have flagged.

The trait reads `turnstile_secret_key` from `SettingRepository`. If the key
is empty or `'0'` (Turnstile not configured) the method returns `true` so the
flow is unchanged for installations that have not enabled Turnstile.

---

## Storage-Key Isolation

The two rate-limit layers use distinct prefixes to avoid GCRA state collision
in shared `FileCache` storage:

| Layer | Key prefix | Limit |
|-------|-----------|-------|
| Route-level `LimitCallback` (per IP) | `sha1('login_route' . $ip)` | 5 / 60 s |
| Controller `checkRateLimit()` (per IP) | `sha1('login_ctrl' . $ip)` | separate counter |
| Route-level `LimitAlways` (global) | `sha1(METHOD + PATH)` | 30 / 60 s |

---

## Relationship to Sign-up Hardening

The same `LimitCallback` + `LimitAlways` + `TooManyRequestsMiddleware`
layering pattern used for the sign-up route (see
[RATE_LIMITER_SIGNUP_HARDENING.md](RATE_LIMITER_SIGNUP_HARDENING.md)) has
been applied consistently to the login route. `TurnstileVerification` is now
the shared trait used by both controllers.

---

*Psalm errorLevel 1 clean. All 3 673 Codeception Unit tests passing (July 2026).*
