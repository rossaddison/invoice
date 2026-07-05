# Change Password Route — Bot Susceptibility Analysis (July 2026)

Assessment of `/change` (`ChangePasswordController::change()`) against the
seven hardening fixes applied to `/login` in
[LOGIN_HARDENING.md](LOGIN_HARDENING.md).

---

## Fix-by-Fix Status

| Fix | Description | Status |
|-----|-------------|--------|
| #1 | `LimitCallback` reading `CF-Connecting-IP` for correct per-IP GCRA buckets | **Missing** — route has no middleware at all |
| #2 | `LimitAlways` global counter (30 / 60 s) collapsing distributed traffic | **Missing** |
| #3 | `TooManyRequestsMiddleware` on both rate-limit layers | **Missing** — no layers to attach it to |
| #4 | Turnstile widget in view + token verified before `FormHydrator` runs | **Missing** on both counts — view has no widget; `$formHydrator->populate()` runs before any IP or token check |
| #5 | Inner per-IP `Counter(5, 60)` decoupled from test-inflated limit | **N/A** — no counter exists yet |
| #6 | `HTTP_CF_CONNECTING_IP` first in `AuthSecurityHelper::getClientIpAddress()` | **Indirectly done** — the header chain was fixed globally, but `AuthSecurityHelper` is not injected into `ChangePasswordController` so the correct IP is never obtained for this route |
| #7 | `checkRateLimit()` called from the action before form processing | **Missing** — `AuthSecurityHelper` absent from constructor; no rate check called |

The bare route declaration in `routes.php` (no middleware):

```php
Route::methods([$mG, $mP], '/change')
    ->action([ChangePasswordController::class, 'change'])
    ->name('auth/change'),
```

---

## Key Mitigating Difference vs `/login`

The `isGuest()` guard at the top of `change()` means unauthenticated bots
cannot reach the POST processing at all. This narrows the threat surface
considerably compared to `/login`.

The realistic attacker is:

- A **compromised authenticated session** spraying old-password guesses
  (brute-forcing the current-password field to confirm it, or attempting to
  lock the account)
- A **malicious authenticated user** scripting rapid changes to evade audit

---

## Priority Gaps

| Gap | Severity | Reason |
|-----|----------|--------|
| No rate limiting on the route | High | Authenticated script can POST thousands of attempts per minute with no throttle |
| `checkRateLimit()` not called | High | Defence-in-depth layer is completely absent |
| `FormHydrator` runs before IP check | Medium | Malformed POST bodies bypass any future token verification |
| No Turnstile on the form | Low–Medium | Lower priority — auth gate, current-password field, and CSRF already provide friction; but inconsistent with the pattern applied to `/login` and `/signup` |

---

## Recommended Fixes

The minimum fix mirrors what was done for `/login`:

1. Add `LimitAlways` + `LimitCallback` closures to the `/change` route in
   `config/common/routes/routes.php` (same pattern as login)
2. Inject `AuthSecurityHelper` into `ChangePasswordController`
3. Call `checkRateLimit()` before `$formHydrator->populate()` on POST
4. *(Optional)* Add Turnstile widget to `resources/views/changepassword/change.php`
   and verify the token before hydration — consistent with login and signup,
   though lower priority given the authentication requirement

Storage-key prefix should be distinct from login and signup counters
(e.g. `sha1('change_ctrl' . $ip)`) to avoid GCRA state collision in shared
`FileCache` storage.

---

*See [LOGIN_HARDENING.md](LOGIN_HARDENING.md) for the fixes already applied
to `/login`, and [RATE_LIMITER_SIGNUP_HARDENING.md](RATE_LIMITER_SIGNUP_HARDENING.md)
for the signup equivalent (July 2026).*
