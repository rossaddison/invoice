# Auth Controllers — Full Hardening Implementation (July 2026)

Following the [login hardening](LOGIN_HARDENING.md) and the
[change-password susceptibility analysis](CHANGE_PASSWORD_BOT_SUSCEPTIBILITY.md),
the same seven-fix pattern was applied to all remaining auth controllers and
their routes. All five auth routes now share a consistent protection posture.

---

## Coverage — All Five Auth Routes

| Route | Controller | Global limit | Per-IP limit | Turnstile | `checkRateLimit()` key |
|-------|-----------|-------------|-------------|-----------|----------------------|
| `/login` | `AuthController` | 30 / 60 s | 5 / 60 s | Yes | `login_ctrl` |
| `/signup` | `SignupController` | 50 / 10 s | 5 / 60 s | Yes | _(via route)_ |
| `/change` | `ChangePasswordController` | 10 / 60 s | 3 / 60 s | Yes | `change_ctrl` |
| `/forgotpassword` | `ForgotPasswordController` | 5 / 60 s | 2 / 60 s | Yes | `forgot_ctrl` |
| `/resetpassword/…/{token}` | `ResetPasswordController` | 10 / 60 s | 3 / 60 s | Yes | `reset_ctrl` |

Limits tighten as the route's legitimate traffic decreases. Forgot-password is
the strictest (2 per IP per minute) because every POST triggers an email send.

---

## What Was Applied to Each Controller

### Fix #1 — `LimitCallback` reading `CF-Connecting-IP`

Each route's inner middleware closure resolves the real client IP with the same
priority chain (`HTTP_CF_CONNECTING_IP` → `HTTP_X_FORWARDED_FOR` → `REMOTE_ADDR`)
and fingerprints the GCRA bucket with a route-specific prefix to avoid storage
key collisions in shared `FileCache`:

| Route | Key prefix |
|-------|-----------|
| `/change` | `sha1('change_route' . $ip)` |
| `/forgotpassword` | `sha1('forgot_route' . $ip)` |
| `/resetpassword/…` | `sha1('reset_route' . $ip)` |

### Fix #2 — `LimitAlways` global path counter

An outer middleware closure wraps each route with a `LimitAlways` counter that
collapses combined distributed-botnet traffic into one shared GCRA bucket,
regardless of IP.

### Fix #3 — `TooManyRequestsMiddleware` on both layers

`TooManyRequestsMiddleware` is registered as `failStoreUpdatedDataMiddleware`
on both the `LimitAlways` and `LimitCallback` layers so that `FileCache` CAS
contention returns 429 instead of silently forwarding the request.

### Fix #4 — Turnstile widget + pre-hydration verify

A `cf-turnstile` widget was added to each view:

- `resources/views/changepassword/change.php`
- `resources/views/forgotpassword/forgotpassword.php`
- `resources/views/resetpassword/resetpassword.php`

The Turnstile JS is conditionally registered only when `turnstile_site_key` is
non-empty, so installations without Turnstile configured are unaffected.

Token verification (`verifyTurnstile()`) runs in a `Method::POST` guard
**before** `$formHydrator->populate()` or `populateFromPostAndValidate()`,
preventing malformed POST bodies from bypassing the challenge.

### Fix #5 — Per-IP `Counter` decoupled from global counter

Each middleware closure constructs its own `Counter` instance inline. The
per-IP counter and the global counter are independent — they share no state
and neither is the DI-bound `CounterInterface` from `rate-limit.php`.

### Fix #6 — `HTTP_CF_CONNECTING_IP` first in `getClientIpAddress()`

`AuthSecurityHelper` was injected into `ChangePasswordController`,
`ForgotPasswordController`, and `ResetPasswordController`. The header priority
chain fix applied to `AuthSecurityHelper::getClientIpAddress()` in the login
hardening therefore now benefits all five controllers.

### Fix #7 — `checkRateLimit()` wired into each action

In-controller defence-in-depth keys are now distinct across all routes:

| Route | In-controller key |
|-------|-----------------|
| `/login` | `sha1('login_ctrl' . $ip)` |
| `/change` | `sha1('change_ctrl' . $ip)` |
| `/forgotpassword` | `sha1('forgot_ctrl' . $ip)` |
| `/resetpassword/…` | `sha1('reset_ctrl' . $ip)` |

---

## `TurnstileVerification` Trait — Now Shared by Four Controllers

`verifyTurnstile()` lives in `src/Auth/Trait/TurnstileVerification.php` and is
used by `AuthController`, `SignupController`, `ChangePasswordController`,
`ForgotPasswordController`, and `ResetPasswordController`. No duplication of
the Turnstile verification logic remains across the auth layer.

---

## Notes per Controller

### `ChangePasswordController` (`/change`)
Requires authentication (`isGuest()` guard) so unauthenticated bots cannot
reach POST processing. Rate limit failure and Turnstile failure redirect to
`site/index`. `AuthSecurityHelper` and `SettingRepository` added to constructor.

### `ForgotPasswordController` (`/forgotpassword`)
Guest-only route that triggers an email send — the most abuse-prone of the
three. `AuthSecurityHelper` added to constructor (`LoggerInterface` was already
present). Rate limit and Turnstile failures redirect back to
`auth/forgotpassword`. `SettingRepository` was already injected.

### `ResetPasswordController` (`/resetpassword/…/{token}`)
Already token-gated: the `{token}` URL parameter is a masked one-time value
derived from a random 32-bit string + timestamp, valid for one hour, validated
against the database via `findIdentityByToken()`. Rate limiting adds
defence-in-depth against token enumeration and CAS silent pass-through.
`$turnstileSiteKey` is looked up **after** the token validation block so the
DB hit is skipped for invalid/expired tokens. Rate limit and Turnstile failures
redirect to `site/resetpasswordfailed` via `failedReset()`. `AuthSecurityHelper`
and `SettingRepository` added to constructor.

---

## Test Infrastructure Fixes

### Repository interfaces for mocking `final` classes

PHPUnit cannot `createMock()` `final` classes. Narrow interfaces were
introduced for the repositories used by the AS4 import service tests:

| Interface | Concrete class |
|-----------|---------------|
| `PurchaseEntryRepositoryInterface` | `PurchaseEntryRepository` |
| `ClientPeppolRepositoryInterface` | `ClientPeppolRepository` |
| `InvRepositoryInterface` | `InvRepository` |
| `InvItemRepositoryInterface` | `InvItemRepository` |
| `SettingRepositoryInterface` | `SettingRepository` |

`CycleOrmAs4MessageRepository` was widened to `DataWriterInterface`
(from the concrete final `EntityWriter`) with a DI binding added to
`config/common/di/as4.php`. `InvTrait4::setDateDue()` was widened to accept
`SettingRepositoryInterface`.

### Dual data-provider annotation for PHPUnit 13 + Codeception

PHPUnit 13 dropped `@dataProvider` docblock support; Codeception requires it.
`As4HttpResponseTest` data-provider methods carry both:

```php
/** @dataProvider providerName */
#[\PHPUnit\Framework\Attributes\DataProvider('providerName')]
public function testSomething(...): void
```

This pattern must be used for any future data-provider test in this project.

---

## Final Test Results

| Suite | Tests | Assertions | Result |
|-------|-------|-----------|--------|
| PHPUnit 13 | 3,727 | 9,982 | OK |
| Codeception Unit | 3,673 | 9,867 | OK |
| Psalm (full run) | — | — | No errors · 99.96% type coverage |

*July 2026*
