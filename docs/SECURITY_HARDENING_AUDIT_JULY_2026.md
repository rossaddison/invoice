# Security Hardening Audit (July 2026)

## Background

A static, config-level review of the app's security posture, covering HTTP
security headers, session/cookie config, CSRF, the public HomeCare QR-scan
endpoint (see
[`docs/HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md`](HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md)),
authentication, RBAC, secrets management, dependency hygiene, file upload
handling, and output-encoding discipline. No dynamic testing was performed;
findings are static/config review only. Nothing in this list has been fixed
yet — this is the punch list to work through.

---

## Critical

### 1. Unrestricted file upload → web root

`src/Invoice/CompanyPrivate/CompanyPrivateController.php:93-140`
(`processLogoFileUpload()`) and `src/Invoice/Product/ProductAttachmentController.php:45-84`
(`imageAttachment()`) write uploaded files directly into `public/logo/` and
`public/products/` — both live, directly web-servable directories — with no
extension whitelist and no MIME-type/content validation. Both read `$_FILES`
directly; the backing `ImageAttachForm` defines no validation rules and isn't
actually consulted. A `.php` file renamed with an image-like name, uploaded by
any authenticated user with product/company-edit permission, lands in the
webroot and executes. No per-directory `.htaccess` in either folder restricts
script execution (only the root `public/.htaccess` blocks `.env`/`.yml`/`.ini`/
`.log`/`composer.*`/`package.*` — not `.php`).

**Fix direction**: whitelist extensions + verify MIME via `finfo`/`getimagesize()`
at upload time, and/or move uploads outside the webroot and serve them through
a controller action that streams the file with a fixed `Content-Type`.

### 2. Session cookie missing the `Secure` flag

`vendor/yiisoft/session/config/params.php:5-11` (the package's own bundled
config) sets `cookie_secure => 0`, and no app-level config in `config/`
overrides it back to `1`. `SessionMiddleware` builds the `Set-Cookie` header
itself from this Yii-level option (`use_cookies=0` disables native PHP cookie
handling), so `docker/prod/php/conf.d/php.ini:4` setting
`session.cookie_secure = 1` is moot — it's not consulted. Compounded by the
HTTPS-redirect rule in `public/.htaccess:129-133` being commented out
("Force HTTPS for PWA (uncomment when SSL is ready)"). Net effect: session
cookies can be transmitted over plain HTTP.

**Fix direction**: add an app-level session config override setting
`cookie_secure => 1` (and confirm the same for
`config/web/di/yii-auth-client.php:98-100`, which also hardcodes
`cookie_secure => 0` for the OAuth-flow session), then uncomment the HTTPS
redirect once TLS is confirmed live in prod.

---

## High

### 3. No rate limiting on the public `/scan/{token}` QR endpoint

`config/common/routes/routes.php:47-49` (`Route::get('/scan/{token}')` →
`HomeCareScan` trait, `src/Invoice/Inv/Trait/HomeCareScan.php:23-51`) has no
middleware at all. Every other public/sensitive route in the same file —
`/login`, `/forgotpassword`, `/resetpassword/{token}`, `/signup`, `/change` —
explicitly attaches `RateLimiter::global(...)` + `RateLimiter::perIp(...)`
(see `RATE_LIMITER_SIGNUP_HARDENING.md` / `LOGIN_HARDENING.md` for the
established pattern). Token entropy is fine
(`ClientService::getOrCreateQrToken()` uses `Yiisoft\Security\Random::string(32)`,
`src/Invoice/Client/ClientService.php:25-35`), but each valid scan triggers a
real side effect — `generateHomeCareCleaningInvoice()` — so the endpoint is
exposed to unlimited token-guessing and scan-triggered invoice-generation
abuse.

**Fix direction**: apply the same `RateLimiter::global`/`perIp` pattern already
used on the other public auth routes.

### 4. Hardcoded cookie-signing secret committed in code

`config/common/params.php:376` —
`'yiisoft/cookies' => ['secretKey' => '53136271c432a1af377c3806c3112ddf']`.
Used by `Yiisoft\Cookies` for signing/validating cookies, including the
"remember me" cookie-login flow (`CookieLoginMiddleware`,
`config/web/params.php:14,72`). It's identical across every deployment/fork
unless manually changed, and it's visible in git history to anyone with repo
access.

**Fix direction**: move to `$_ENV['COOKIE_SECRET_KEY']` (documented in
`.env.example` with no literal value, matching every other secret in
`config/common/params.php`), generate a unique value per deployment, and
rotate the committed key since it must be treated as already compromised.

---

## Medium

### 5. HSTS and Permissions-Policy headers missing; other headers only in `.htaccess`

CSP is enforced at the app layer via
`src/Middleware/ContentSecurityPolicyMiddleware.php:31-42`
(config: `config/web/params.php:81-100+`). But `X-Frame-Options`,
`X-Content-Type-Options`, `X-XSS-Protection`, and `Referrer-Policy` exist only
in `public/.htaccess:6-53` (Apache `mod_headers`) — not as PHP middleware.
Anything deployed behind a different web server, a proxy that strips headers,
or PHP's built-in dev server (`public/index.php:20-27`) silently loses those
headers. No `Strict-Transport-Security` or `Permissions-Policy` header exists
anywhere, PHP or Apache.

**Fix direction**: add HSTS + Permissions-Policy to `public/.htaccess` (and
consider promoting all of these into a PHP middleware, mirroring
`ContentSecurityPolicyMiddleware`, so they're guaranteed regardless of the web
server in front of the app).

### 6. CSP allows `'unsafe-inline' 'unsafe-eval'` for scripts

`config/web/params.php:84` (mirrored in `public/.htaccess:19`) — see
[`docs/CONTENT_SECURITY_POLICY_UPDATES.md`](CONTENT_SECURITY_POLICY_UPDATES.md)
for prior CSP work. This significantly weakens CSP's value as an XSS backstop.

**Fix direction**: audit inline `<script>`/`onclick=` usage across views and
migrate to nonce-based or external-script CSP once inline script usage is
inventoried — likely a multi-session effort given the number of views.

### 7. 2FA is opt-in; no per-account login lockout

TOTP 2FA exists (`src/Auth/Trait/TwoFactorAuth.php`,
`src/Auth/Controller/AuthTfaHelper.php`, `OTPHP\TOTP`, recovery codes via
`src/User/RecoveryCodeService.php`) but is gated by both a global setting and a
per-user flag (`TwoFactorAuth.php:357`) — not enforced for any role, including
admin. Separately, `src/Middleware/RateLimiter.php` provides IP/global request
throttling on auth routes (see `LOGIN_HARDENING.md`) but there is no
failed-login-attempt counter tied to a specific *account* — a distributed
credential-stuffing attempt spread across many IPs against one account
wouldn't be blocked.

**Fix direction**: consider requiring 2FA for the admin role at minimum;
consider an account-level failed-attempt counter (e.g. lock/require 2FA
step-up after N failures within a window) as defense-in-depth alongside the
existing IP-based limiter.

---

## Low

### 8. One confirmed unescaped output

`resources/views/invoice/inv/modal_change_client.php:46` renders
`$client->getClientName()` inside an `<option>` without `Html::encode()`,
while sibling files handling the identical call —
`resources/views/invoice/inv/modal_copy_inv.php:69`,
`resources/views/invoice/inv/modal_copy_inv_multiple.php:74` — encode it
correctly. `client_name` is user-editable free text, so this is a plausible
stored-XSS vector for any authenticated user who can view the "change client"
modal. Looks like a copy-paste miss rather than a systemic gap — the
`Html::encode()`/`H::encode()` convention is otherwise applied consistently
across the ~150+ view files spot-checked.

**Fix direction**: wrap the line in `Html::encode()` to match its siblings; a
full grep sweep for `<?= $` not wrapped in `Html::encode`/`H::encode` across
all views would be worth doing to rule out further instances.

### 9. RBAC enforcement is opt-in per route (fail-open)

`src/Middleware/RoutePermission.php` requires each route to explicitly call
`RoutePermission::check(...)` or `::invoiceGroup(...)`
(`AccessChecker` middleware, `src/Middleware/AccessChecker.php:31-48`) — there
is no deny-by-default enforcement at the router level. This is how
`/scan/{token}` ended up unauthenticated (intentionally, in that case), but it
means a newly added route can silently become public if a developer forgets
the wrapper. A full per-route audit to confirm no unintentional gaps exists
was out of scope for this pass.

**Fix direction**: consider a CI check (or a route-config linter) that fails
the build if a new route under `/invoice` lacks a `RoutePermission` wrapper
and isn't on an explicit public-route allowlist.

---

## Confirmed solid (no action needed)

- **CSRF**: globally enforced, double-registered
  (`config/common/di/router.php:40` + `config/web/params.php:70`); no route
  found bypassing it.
- **Password hashing**: bcrypt via `Yiisoft\Security\PasswordHasher`
  (`src/Infrastructure/Persistence/User/User.php:83,88`).
- **Token generation**: QR tokens and other security-sensitive tokens use
  `Yiisoft\Security\Random::string()` (CSPRNG-backed).
- **Auth rate limiting**: `/login`, `/forgotpassword`, `/resetpassword/{token}`,
  `/signup`, `/change` all have `RateLimiter::global`/`perIp` — see
  `AUTH_CONTROLLERS_HARDENING.md`, `LOGIN_HARDENING.md`,
  `RATE_LIMITER_SIGNUP_HARDENING.md`.
- **Secrets**: `.env` is gitignored (`.gitignore:22`), `.env.example` documents
  variable names with no literal values, and no hardcoded DB/OAuth/mailer
  secrets were found in `config/` or `src/` (the one exception is the cookie
  secret key, tracked as High #4 above).
- **Dependency hygiene**: PHP pinned to `8.4 - 8.5` (`composer.json:20`),
  `renovate.json` configured for automated updates.

---

## Priority order for follow-up work

1. Unrestricted file upload → web root (Critical)
2. Session cookie `Secure` flag (Critical)
3. Rate limiting on `/scan/{token}` (High)
4. Hardcoded cookie-signing secret (High)
5. HSTS / Permissions-Policy / header enforcement layer (Medium)
6. CSP `unsafe-inline`/`unsafe-eval` (Medium)
7. Mandatory 2FA for privileged roles + account-level lockout (Medium)
8. `modal_change_client.php` encoding fix + view sweep (Low)
9. RBAC fail-open architecture review (Low)
