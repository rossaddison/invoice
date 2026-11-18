# Signup hardening against bot waves (November 2026)

The public signup routes (`/signup` and `/homecare-signup`) are open to anyone, so they are where bot waves
arrive. They are not proxied through Cloudflare and have no IP range locking, so the protection has to be
in the application. This page lists what now stands in front of them, in the order a request meets it.

| Layer | Where | What it does |
|---|---|---|
| Rate limits | `config/common/routes/routes.php`, `routes-homecare-signup.php`, `App\Middleware\RateLimiter` | a path-wide counter (`global(50, 10)`: 50 per 10 seconds, whoever asks) and a per-real-IP counter (`perIp(5, 'signup')`: 5 per 60 seconds) |
| Real client IP | `App\Service\ClientIpResolver`, `config/common/di/client-ip.php` | `CF-Connecting-IP` / `X-Forwarded-For` are believed only when the connection itself comes from a trusted proxy |
| Turnstile, fail-closed | `TurnstileVerification::verifyTurnstileRequired()` | a POST is refused when no Turnstile secret is configured, not waved through |
| Field limits | `SignupForm`, `HomeCareSignupForm` | a bot cannot fill the database or the pending table with huge values |
| Login format | `SignupForm::LOGIN_PATTERN` | letters, digits, dots, underscores and hyphens only, starting with a letter or digit |

## Real client IP

A per-IP limit is useless if the visitor chooses their own IP. The limiter used to read forwarding headers
from anyone. `ClientIpResolver` now trusts `CF-Connecting-IP` and `X-Forwarded-For` only when the peer
address is inside `app.trustedProxies` in `config/common/params.php`, which holds Cloudflare's published
ranges. Anyone else is identified by `REMOTE_ADDR`, so sending a header changes nothing.

If you put another proxy or load balancer in front of the site, add its address range to
`app.trustedProxies`, otherwise every visitor will appear to come from that one address.

## Turnstile: strict on signup, unchanged elsewhere

`TurnstileVerification` has two checks that share one request to Cloudflare:

| Method | No secret key configured | Used by |
|---|---|---|
| `verifyTurnstileRequired()` | **refuses** (blank secret or `'0'`) | `/signup`, `/homecare-signup` |
| `verifyTurnstile()` | lets the request through | login, forgot, reset and change password |

So a signup form on a server where the Turnstile secret is missing refuses every signup, which is the
safe direction. Set both keys under *Settings, Turnstile*. Cloudflare publishes a test secret
(`1x0000000000000000000000000000000AA`) that always passes, which is handy for a local install. The
settings page text and tooltip say this.

The other forms still fail open when no secret is set. Making them strict is possible but changes who can
log in on a server with no keys, so it was left as a separate decision.

## Field limits and the login rule

| Field | Rule |
|---|---|
| login | 3 to 48 characters, `/^[\p{L}\p{N}][\p{L}\p{N}._-]*$/uD` |
| password | 8 to 128 characters |
| email | up to 254 characters |
| HomeCare: client name / surname / street / building number | 50 / 151 / 100 / 10 (the pending table column is `string(10)`) |

The pattern ends with the `D` modifier, because without it `$` also matches just before a trailing
newline, and `"jane\n"` would pass. The error message key is `validator.login.format`; only English has
text for it so far, other languages fall back to English.

## Known limits

- The other Turnstile forms are still lenient (see above).
- The new messages exist in English only.

## Related

- [Signup confirmation link](SIGNUP_CONFIRMATION_LINK_NOVEMBER_2026.md)
- [Unconfirmed signup cleanup](UNCONFIRMED_SIGNUP_CLEANUP_NOVEMBER_2026.md)
- [Live test checklist](SIGNUP_AND_LOGIN_LIVE_TEST_CHECKLIST_NOVEMBER_2026.md)
- [Turnstile CSP fix](TURNSTILE_CSP_FIX.md)
