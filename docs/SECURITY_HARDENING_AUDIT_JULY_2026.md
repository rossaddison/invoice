# Security Hardening Audit (July 2026)

## Background

A static, config-level review of the app's security posture, covering HTTP
security headers, session/cookie config, CSRF, the public HomeCare QR-scan
endpoint (see
[`docs/HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md`](HOMECARE_QR_AUTOINVOICE_AND_ROUTES_REFACTOR.md)),
authentication, RBAC, secrets management, dependency hygiene, file upload
handling, and output-encoding discipline. No dynamic testing was performed;
findings are static/config review only.

---

## Critical

### 1. Unrestricted file upload → web root — ✅ Fixed 2026-07-15

`src/Invoice/CompanyPrivate/CompanyPrivateController.php` (`add()`/`edit()`)
and `src/Invoice/Product/ProductAttachmentController.php` (`imageAttachment()`)
now read uploads via PSR-7 `getUploadedFiles()` instead of raw `$_FILES`, and
validate every upload with `Yiisoft\Validator\Rule\File` before moving it —
whitelisting extension, whitelisting MIME type (sniffed from the real file
content via `mime_content_type()`, not the client-supplied header), and
capping size at 5MB. Moves go through `UploadedFileInterface::moveTo()`
instead of manual `move_uploaded_file()`. Product images reuse the existing
`ProductImageRepository::getContentTypes()` whitelist (gif/jpg/jpeg/png/bmp/
tiff); company logos use the same whitelist as a local controller constant.

**Not fixed**: storage still lands in `public/logo/` and `public/products/` —
still web-servable directories. The "move outside webroot + stream through a
download action" half of the original fix direction is deferred; reference
pattern (Flysystem-backed storage interface + a dedicated download action
using `Yiisoft\ResponseDownload\DownloadResponseFactory`) is in
`yiisoft/demo-summarizer`'s `src/Document/Infrastructure/` and
`src/Web/Document/DownloadAction.php`, but that repo isn't a project
dependency — would mean adding `league/flysystem` and
`yiisoft/response-download`, or hand-rolling the equivalent.

### 2. Session cookie missing the `Secure` flag — ✅ Fixed 2026-07-15 (opt-in)

`vendor/yiisoft/session/config/params.php:5-11` (the package's own bundled
config) sets `cookie_secure => 0`. Added an app-level override in
`config/web/params.php` reading `(int) $_ENV['SESSION_COOKIE_SECURE']`
(parsed as a boolean in `autoload.php`, default `false`) instead of a
hardcoded `1`.

That default-`false` design wasn't the original plan — it exists because
actually running the app after setting `cookie_secure => 1` unconditionally
surfaced a real regression: `Yiisoft\Session\SessionMiddleware::commitSession()`
throws a hard `SessionException` on **every** request ("cookie_secure is on
but connection is not secure") whenever `cookie_secure` is on and the
request scheme isn't `https` — confirmed by curling the local WAMP dev
instance and getting a 500 with that exact error. Since the HTTPS redirect
is still commented out (see below), this means the current environment isn't
guaranteed to be HTTPS end-to-end, and forcing `cookie_secure => 1`
unconditionally would have broken every deployment that isn't already behind
confirmed TLS — including, possibly, prod right now. Made it an explicit
per-deployment opt-in (`SESSION_COOKIE_SECURE=true` in `.env`) instead, to be
flipped on together with the HTTPS redirect once TLS is confirmed live —
verified by re-curling after the fix: the `SessionException` is gone and the
`Set-Cookie` header has no `Secure` flag while `SESSION_COOKIE_SECURE` is
unset, as expected for plain HTTP.

Investigating `config/web/di/yii-auth-client.php:96-103` (the "OAuth-flow
session" the audit flagged) turned up a worse bug than described: that file
didn't just configure a *separate* session for OAuth — it rebound
`SessionInterface::class => Session::class` wholesale, and because
`web/di/*.php` loads after `yiisoft/session`'s own `di-web.php` in the merge
plan, this hardcoded, params-blind `Session::class => [cookie_secure => 0]`
definition was silently replacing the **entire app's session wiring**,
including normal login (`CurrentUser::withSession()`) — not an isolated
OAuth-only concern. Fixed by removing the shadow `Session::class`/
`SessionInterface::class` bindings entirely and pointing the AuthClient
`$constructArray['session']` at `Reference::to(SessionInterface::class)`
instead, so there's a single session wired from `di-web.php` → params, with
no override collision. Verified via a script instantiating
`Yiisoft\Config\Config` with the real merge plan and confirming the merged
`di-web` group binds `SessionInterface::class` to the vendor definition, and
that no separate `Session::class` binding remains.

**Still open**: the HTTPS-redirect rule in `public/.htaccess:129-133` is still
commented out ("Force HTTPS for PWA (uncomment when SSL is ready)"), and
`SESSION_COOKIE_SECURE` is still `false` everywhere. Both need to be flipped
on together, by whoever manages the prod TLS termination, once HTTPS is
confirmed live end-to-end — flipping only one of the two either does nothing
(redirect on, cookie still insecure) or breaks every request (cookie secure,
no TLS).

---

## High

### 3. No rate limiting on the public `/scan/{token}` QR endpoint — ✅ Fixed 2026-07-15

`config/common/routes/routes.php` (`Route::get('/scan/{token}')` →
`HomeCareScan` trait, `src/Invoice/Inv/Trait/HomeCareScan.php:23-51`) had no
middleware at all, unlike every other public/sensitive route in the same
file. Added the same `RateLimiter::global`/`perIp` pattern already used on
`/login`/`/forgotpassword`/`/resetpassword`/`/signup`/`/change`: 60 scans per
60s globally, 10 per 60s per real IP (salt `homecare_scan_route`). The perIp
limit is set higher than the auth routes' since a legitimate cleaning
business may scan several different clients' QR codes in quick succession
from one device — these numbers are a reasonable starting point, not a tuned
figure; adjust if real usage patterns turn out busier or the limiter proves
too strict.

### 4. Hardcoded cookie-signing secret committed in code — ✅ Fixed 2026-07-15

`config/common/params.php` had `'yiisoft/cookies' => ['secretKey' =>
'53136271c432a1af377c3806c3112ddf']` — identical across every
deployment/fork, visible in git history to anyone with repo access, and used
by `Yiisoft\Cookies` for signing/encrypting cookies, including the
"remember me" cookie-login flow. Changed to `$_ENV['COOKIE_SECRET_KEY'] ??
''` (documented in `.env.example`, matching the fallback-to-empty convention
every other secret in this file already uses). A fresh 32-byte key
(`php -r "echo bin2hex(random_bytes(32));"`) was generated and added to the
local, gitignored `.env` so this dev checkout doesn't silently run with an
empty key. Verified by dumping the real merged config with `.env` loaded —
resolves to the new 64-char value, not the old hardcoded one.

**Still needed**: the committed key (`53136271c432a1af377c3806c3112ddf`,
still visible in git history) must be treated as compromised. Any other
deployment of this codebase — prod, staging, other forks — needs to generate
and set its own `COOKIE_SECRET_KEY`, which will invalidate existing signed
"remember me" cookies (users simply log in again; no data loss). That's a
per-deployment operational step outside what a code change here can do.

---

## Medium

### 5. HSTS and Permissions-Policy headers missing; other headers only in `.htaccess` — ✅ Fixed 2026-07-15

CSP was already enforced at the app layer via
`src/Middleware/ContentSecurityPolicyMiddleware.php`, but `X-Frame-Options`,
`X-Content-Type-Options`, `X-XSS-Protection`, and `Referrer-Policy` existed
only in `public/.htaccess` (Apache `mod_headers`) — not as PHP middleware —
and no `Strict-Transport-Security` or `Permissions-Policy` header existed
anywhere, PHP or Apache.

Added `src/Middleware/SecurityHeadersMiddleware.php`, mirroring
`ContentSecurityPolicyMiddleware`'s pattern: a config-driven header map
(`config/web/params.php` → `security-headers.headers`, wired via
`config/web/di/security-headers.php`) applied to every response, registered
in the app's middleware stack right after the CSP middleware. Covers all
four existing headers plus the two new ones — HSTS
(`max-age=31536000; includeSubDomains`; harmless to send now since browsers
ignore it entirely over plain HTTP, so it's ready ahead of the HTTPS
redirect) and Permissions-Policy (denies camera/microphone/geolocation/
payment/usb/magnetometer/gyroscope/accelerometer — none of which this app's
own JS calls; QR codes are scanned by the visitor's separate phone camera
app, not in-page `getUserMedia`). Also added the same two new headers to
`public/.htaccess` per the original fix direction, so Apache-fronted
deployments get them even without the PHP layer.

Verified against the actual running app (curled the local WAMP instance):
all six headers present in the real response, both from the PHP middleware
and — while investigating a regression this same work surfaced (see finding
#2's `SESSION_COOKIE_SECURE` opt-in) — confirmed a plain HTTP response no
longer 500s and still carries every header correctly.

### 6. CSP allows `'unsafe-inline' 'unsafe-eval'` for scripts — ✅ Fixed 2026-07-15, remaining exception fixed 2026-07-16

`config/web/params.php` (mirrored in `public/.htaccess`) had `'unsafe-inline'
'unsafe-eval'` in `script-src`. The initial text-only inventory undercounted
badly — it missed every inline script built via PHP's `Html::script()`
helper (no literal `<script` text to grep for), including both core layouts
(`layout/guest.php`, `layout/invoice.php`) used on every page. The real
count: 12 files with inline script content, 11 with inline `onclick=`
attributes, and 6 uses of htmx's `hx-on::after-request` — which turned out to
depend on `'unsafe-eval'` specifically: htmx implements `hx-on:` via
`new Function(...)` internally.

**What changed:**
- All inline script logic extracted into new TS modules
  (`flash-message-timer.ts`, `family-form-validation.ts`, `data-actions.ts`,
  `htmx-hooks.ts`, `customfield-position.ts`, `faq-pages.ts`) bundled into
  the existing `invoice-typescript-iife.js`, self-initializing unconditionally
  (each guarded by checking for its own target DOM elements, so it's a no-op
  on pages that don't need it).
- `onclick="this.showPicker()"` / `window.close()` / `window.print()` /
  `return confirm(...)` replaced with `data-action`/`data-confirm`
  attributes read by one delegated `click` listener in `data-actions.ts`.
- `hx-on::after-request` replaced with `data-hx-reset-on-success` markers and
  a delegated `htmx:afterRequest` listener in `htmx-hooks.ts`; `htmx.config.allowEval`
  explicitly set to `false` as defense in depth now nothing depends on it.
- Along the way, found and collapsed real duplication: `inv/guest.php` carried
  its own ~250-line copies of `InvoiceAmountMagnifier`/group-toggle/
  `MobilePreviewToggle` that nearly duplicated `list-utils.ts`/`inv-index.ts`
  (already used by the authenticated `inv/index.php`). Parameterized
  `initInvIndex(tableId, configElId)` so the guest page reuses the same
  code, and deleted the genuinely-dead, superseded `mobile-preview.ts`
  (an abandoned iframe-based design — the comment on the live version
  explains why it was replaced with a popup-window approach).
- Dynamic server data that used to be interpolated directly into inline
  `<script>` blocks (customfield position map, modal textarea body, filter
  prompt labels) now flows through `<script type="application/json">` data
  islands instead — inert as far as CSP `script-src` is concerned, read via
  `JSON.parse` by the corresponding TS module.
- `script-src 'unsafe-inline' 'unsafe-eval'` → `script-src 'self'` in both
  `config/web/params.php` and `public/.htaccess`.

**Verified for real, not just by test suite** (this is a change where
mistakes fail silently in the browser, not as a server error): installed
Playwright, created a throwaway console user, and drove the app in headless
Chromium watching the DevTools console for CSP violations. This caught a
real bug the static inventory missed — `src/Auth/Controller/AuthController.php`
passed an inline `fadeOutJS` (`Html::script()`) into `resources/views/auth/login.php`
to fade out the "TFA enabled" badge, on a page that doesn't even load the
main bundle. Moved that logic into `src/Auth/Asset/keypad-copy-to-clipboard.ts`
(the smaller bundle the auth pages *do* load) and confirmed the violation
disappeared on re-test. Full Psalm, PHPUnit (×2 suites), and vitest all
clean throughout.

**Known remaining exception — fixed 2026-07-16, in the vendor package's own
repo**: the login page showed 2 CSP violations, both from the `AuthChoice`
widget in `rossaddison/yii-auth-client` (a separate forked package,
installed via a VCS repository in `composer.json`, checked out standalone
at `C:\wamp64\www\yii-auth-client`, branch `invoice`) — one inline script
registering `#yii-auth-client` handlers (`WebView::registerJs()` in
`init()`), one `onclick="window.open(...)"` on the Facebook login link
(`authRoutedButtons()`). As predicted, patching
`vendor/rossaddison/yii-auth-client/...` directly wouldn't have persisted —
fixed instead in that package's own repo (commit `9b9367f`, pushed to
`origin/invoice`), then pulled in here via
`composer update rossaddison/yii-auth-client` (`composer.lock` now pins
`9b9367f`).

**What changed** (in `rossaddison/yii-auth-client`): `init()` now sets a
`data-authchoice` attribute (JSON client options, or empty) on the widget's
container div instead of registering inline JS; `authchoice.js`'s existing
`[data-authchoice]` auto-init (on `DOMContentLoaded`) picks it up. Removing
`AuthChoice`'s `registerJs()` call also removed the `WebView` constructor
dependency, so `config/web/di/yii-auth-client.php`'s `AuthChoice::class`
definition was trimmed to match (also dropped a stray `translator` arg that
didn't correspond to any constructor parameter). `authRoutedButtons()`
drops the inline `onclick` entirely — `clientLink()` already emits
`data-popup-width`/`data-popup-height` in popup mode, and the delegated
click listener in `authchoice.js` opens the popup from those, same as the
CSP-safe path every other provider link already used.

Along the way, found and fixed a **real, separate bug** in
`authchoice.js`: it called an `extend(...)` helper that was never defined
anywhere in the file. The delegated click-to-popup path had been silently
broken all along — masked only because the (now-removed) inline `onclick`
bypassed it entirely and opened the popup directly. Once the onclick was
gone, this would have broken the login popup outright, so a minimal
`extend()` (shallow object merge) was added.

Separately (unrelated to CSP, not yet investigated): the login page's HTML
response nests three copies of `<div id="yii-auth-client">...</div>`
(unclosed, then re-opened) instead of one — `AuthChoice::widget()` echoes
its container `<div>` directly from `init()` rather than returning it, and
`resources/views/auth/login.php` never calls `AuthChoice::end()`/`render()`
to close it; combined with the view layer's multi-pass rendering (collecting
registered assets before final output), the constructor's direct echo fires
on every pass. Harmless in practice — the browser's tag-soup parser still
produces a single `#yii-auth-client` element via `getElementById()`, and the
real OAuth link ends up a descendant of it either way — but the underlying
"echo instead of return" pattern in that vendor widget is worth a look
independent of this audit.

**Verified for real, not just Psalm**: ran `./yii serve` locally, drove the
login page with headless Playwright — zero CSP `script-src` console
violations (down from 2) against the real, composer-installed vendor
package, and the "Continue with Facebook" button still opens its popup via
the delegated listener. Psalm clean in both repos.

### 7. 2FA is opt-in; no per-account login lockout — ✅ Fixed 2026-07-15

TOTP 2FA existed (`src/Auth/Trait/TwoFactorAuth.php`,
`src/Auth/Controller/AuthTfaHelper.php`, `OTPHP\TOTP`, recovery codes via
`src/User/RecoveryCodeService.php`) but was gated by a single global setting
(`enable_tfa`) — when off, 2FA was skipped for everyone, including admins.
Separately, the IP-keyed rate limiter on `/login` (see `LOGIN_HARDENING.md`)
had no failed-login-attempt counter tied to a specific *account* — a
distributed credential-stuffing attempt spread across many IPs against one
account wouldn't have been blocked.

**What changed:**
- `src/Auth/Controller/AuthController.php`'s `resolveLoginResponse()` now
  routes admins into the 2FA path (`handleTfaPath()`) unconditionally,
  regardless of the `enable_tfa` setting: `if ($this->sR->getSetting('enable_tfa')
  == '1' || $this->isAdminUser($userId))`. An admin without TOTP set up still
  gets redirected into `auth/showSetup` — same mechanism already used for
  everyone else when the global setting is on, just no longer skippable for
  that role.
- `src/Auth/Controller/AuthSecurityHelper.php` gained
  `checkAccountRateLimit(string $login)`: a second, independently-configured
  `Counter` (5 attempts / 900s) keyed by `hash('sha256', 'login_account_' .
  mb_strtolower($login))`, reusing the *same* file-cache-backed
  `StorageInterface` the existing IP-keyed limiter already uses — no schema
  changes, no new tables. Wired into `AuthController::login()` alongside the
  existing IP check, so a login attempt is blocked if *either* the IP or the
  target account has exceeded its budget.
- Scoped deliberately lightweight per discussion: this counts login
  *attempts* per account (not strictly *failures*, since the underlying GCRA
  counter algorithm increments on every check) — simpler and consistent with
  how the existing IP-based limiter already works, at the cost of a
  successful login also consuming one unit of budget. A fuller design
  (dedicated failure-only counter, persistent lock state visible to an
  admin) was considered and explicitly deferred as a heavier, separate
  feature rather than a config-level fix.

**Verified:** Psalm clean (file-level and full project), PHPUnit clean (both
suites), confirmed via live HTTP requests against the running app that
distinct cache entries are created per account key (separate from the
IP-keyed ones) and that the login page still loads correctly with the new
DI dependency (`StorageInterface` now also injected into `AuthController`).

---

## Low

### 8. One confirmed unescaped output — ✅ Fixed 2026-07-15 (found 3 more via sweep)

`resources/views/invoice/inv/modal_change_client.php:46` rendered
`$client->getClientName()` inside an `<option>` without `Html::encode()`,
while sibling files handling the identical call —
`modal_copy_inv.php`, `modal_copy_inv_multiple.php` — encoded it correctly.
Fixed by wrapping in `Html::encode()` (import added, file had none before).

Did the recommended grep sweep for `<?=` output not wrapped in
`Html::encode`/`H::encode` across all views. Naive single-line grepping
undercounts here too, same lesson as finding #6 — several genuinely-safe
lines are multi-line ternaries with the `Html::encode()` call on the
*following* line (e.g. `client_delivery_location_list.php`'s address
fields, `invitem/_partial_item_table_modal.php`'s product/task
description) — false positives on a naive scan, confirmed safe on
inspection. Filtering those out, found **3 more real, unescaped instances**
beyond the one the audit already flagged, all the same "sibling file
encodes, this one doesn't" shape:

- `resources/views/invoice/product/modal_product_lookups_quote.php:44` —
  `$family->getFamilyName()` unescaped in an `<option>`; the `inv` sibling
  (`modal_product_lookups_inv.php:69`) encodes the identical call correctly.
- `resources/views/invoice/product/views/partial_product_gallery.php:25` —
  `$product->getProductName()` unescaped.
- `$custom->getLabel()` (custom-field label, admin/authorized-user-editable)
  unescaped in all 4 of `resources/views/invoice/emailtemplate/template-tags-{inv,quote,with-inv,with-quote}.php`
  — one recurring copy-pasted block, not 4 independent misses.

All fixed the same way: `Html::encode()`/`H::encode()` added, matching each
file's existing import-alias convention, `use` statement added where absent.
Sweep covered client/company/family/product names, addresses, custom-field
labels, and several other free-text-getter patterns across ~150 view files;
not exhaustive, but a real pass rather than trusting the single confirmed
instance was the only one. Verified: Psalm clean (all touched files + full
project), PHPUnit clean (both suites).

### 9. RBAC enforcement is opt-in per route (fail-open) — ✅ Investigated 2026-07-15, found a Critical gap + a functional bug affecting 5 routes

`src/Middleware/RoutePermission.php` requires each route to explicitly call
`RoutePermission::check(...)` or `::invoiceGroup(...)` (`AccessChecker`
middleware) — there's no deny-by-default enforcement at the router level.
The original pass flagged this as a theoretical risk and left the full
per-route audit out of scope. Doing that audit (walking all 73 files under
`config/common/routes/`) turned up two concrete, real issues:

**Critical — found, not theoretical**: `config/common/routes/routes-backend.php`
had **zero** protection on all 9 HMRC routes — no group wrapper, no
per-route check, nothing. `vatReturnSubmit` (submits a VAT return to HMRC),
`vatObligations`, `vatReturnPrepare`, `selfEmploymentBusinesses`, and a
sandbox test-user creator were reachable by any anonymous visitor; the only
gate was a per-visitor-session HMRC OAuth token, not a requirement to be a
logged-in user of this app at all. Confirmed live via curl before the fix
(200 with real content) and after (302 to `/login`).

**Fixed**: added a new `Permissions::MANAGE_HMRC` permission
(`src/Auth/Permissions.php`), granted only to the `admin` role in
`resources/rbac/items.php` (kept distinct from `edit.inv`/`view.inv`
deliberately — those already happen to be admin-only today too, but reusing
them would silently extend HMRC access to any future role that picks up
`edit.inv` for an unrelated reason). Wrapped the 8 non-host-scoped
`routes-backend.php` routes in a `Group::create('/backend/hmrc')` with
`Authentication::class` + `RoutePermission::check(Permissions::MANAGE_HMRC)`
— not `RoutePermission::invoiceGroup()`, since that prepends `/invoice` and
would have changed these routes' URLs.

**A second, different-shaped bug found along the way**: 5 routes are wrapped
in `RoutePermission::invoiceGroup()` (which requires a logged-in session)
despite being designed for external callers that will never have one, each
with its own internal secret/token check:
`invrecurring/cron` (cron_key query param), `telegram/webhook`
(`X-Telegram-Bot-Api-Secret-Token` header), `userinv/signup` (masked
time-limited token — a brand-new invitee has no account yet), `as4/receive`
(AS4/ebMS3 message signing), `peppol/inbound/delivery` (Oxalis access
point). `Yiisoft\Auth\Middleware\Authentication` hard-rejects any request
with no identity unless the path is marked optional, which none of these
were — so all 5 were very likely non-functional in practice, rejected before
ever reaching their own check. Two of the five even had comments in the
route file itself asserting "No auth middleware" that the code
contradicted. Fixed by pulling all 5 out of `invoiceGroup()` into standalone
top-level routes, matching the existing `/scan/{token}` precedent
(`routes.php`) for "public by design, gated by its own token check."
Verified live: `invrecurring/cron` now returns its own `{"success":false,
"error":"Forbidden"}` instead of a login redirect; `telegram/webhook` and
`as4/receive` return 422 (reaching real parsing/validation) instead of 302;
`peppol/inbound/delivery` returns 200; `userinv/signup` with a bogus token
now redirects to `site/index` (its own invalid-token path) instead of
`/login`.

**Also fixed (2026-07-15, follow-up)**: `routes-quote-allowance-charge.php`'s
`add` action was missing the `RoutePermission::check(EDIT_INV)` its
edit/delete/view siblings all have — still required being logged in
(protected by the outer group), but any authenticated user regardless of
role could reach it. One-line fix, added the same middleware its siblings
already use. Verified: Psalm clean, PHPUnit clean, route still resolves
(confirmed unauthenticated requests still redirect to `/login` at
`/invoice/quoteallowancecharge/add/{quote_id}`, same as before — the fix's
effect, blocking authenticated-but-under-permissioned users, follows the
same code path already proven correct on its 3 sibling routes).

**Fix direction** (original recommendation, still not done): consider a CI
check or route-config linter that fails the build if a new route under
`/invoice` lacks a `RoutePermission` wrapper and isn't on an explicit
public-route allowlist. Doing the audit by hand once was tractable at 73
files; it won't stay that way as more routes get added.

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

1. ~~Unrestricted file upload → web root (Critical)~~ — fixed 2026-07-15,
   webroot storage still open
2. ~~Session cookie `Secure` flag (Critical)~~ — fixed 2026-07-15 as an
   opt-in (`SESSION_COOKIE_SECURE`); HTTPS redirect still commented out
   pending TLS confirmation, both need flipping on together
3. ~~Rate limiting on `/scan/{token}` (High)~~ — fixed 2026-07-15
4. ~~Hardcoded cookie-signing secret (High)~~ — fixed 2026-07-15, prod key
   rotation still needed
5. ~~HSTS / Permissions-Policy / header enforcement layer (Medium)~~ — fixed
   2026-07-15
6. ~~CSP `unsafe-inline`/`unsafe-eval` (Medium)~~ — fixed 2026-07-15; the
   one exception (AuthChoice widget in the forked `rossaddison/yii-auth-client`
   vendor package) fixed 2026-07-16 in that package's own repo, pulled in
   via `composer update`
7. ~~Mandatory 2FA for privileged roles + account-level lockout (Medium)~~ —
   fixed 2026-07-15 (lightweight account-rate-limit variant, not full lockout)
8. ~~`modal_change_client.php` encoding fix + view sweep (Low)~~ — fixed
   2026-07-15; sweep found 3 more real instances beyond the one flagged
9. ~~RBAC fail-open architecture review (Low)~~ — investigated 2026-07-15;
   found and fixed a real Critical gap (unprotected HMRC routes), 5 broken
   webhook-style routes, and the `quoteallowancecharge/add` permission gap;
   CI-check recommendation itself still not done
