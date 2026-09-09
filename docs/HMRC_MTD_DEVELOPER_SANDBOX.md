# HMRC MTD Developer Sandbox — OAuth2 Backend Integration

**Date:** July 2026  
**Status:** Implemented — `HmrcApiCatalogue`, `backend/hmrc` dashboard, PKCE OAuth flow for authenticated admins, login-page button removed

---

## Overview

Yii3-i connects to HMRC's Making Tax Digital (MTD) Developer Sandbox via OAuth2 to
authorise API calls for VAT, Self Assessment, Self-employed Business, and related
services. This document records the design decisions and implementation changes that
separated the HMRC OAuth flow into its correct role: **API authorisation for an
already-authenticated admin**, not a user-identity login mechanism.

---

## HmrcApiCatalogue (`src/Auth/Client/HmrcApiCatalogue.php`)

A curated catalogue of eight HMRC MTD APIs, keyed by context path.

| Context path | API name | Needs | Scopes |
|---|---|---|---|
| `organisations/vat` | VAT (MTD) | VRN | `read:vat` `write:vat` |
| `individuals/self-assessment` | Self Assessment (Individual) | NINO | `read:self-assessment` `write:self-assessment` |
| `individuals/business/self-employment` | Self-employed Business | NINO | `read:self-employment` `write:self-employment` |
| `individuals/business/details` | Business Details | NINO | `read:self-assessment` |
| `individuals/calculations` | Individual Calculations | NINO | `read:self-assessment` `write:self-assessment` |
| `individuals/income-received` | Income Received | NINO | `read:self-assessment` `write:self-assessment` |
| `individuals/national-insurance` | National Insurance Record | NINO | `read:national-insurance-record` |
| `customs/declarations` | Customs Declarations | EORI | `write:customs-declaration` |

Key static methods:

- `all()` — full catalogue array
- `allScopes()` — space-separated string of every unique scope; used as `getDefaultScope()` in `DeveloperSandboxHmrc`; HMRC silently drops unsubscribed scopes so requesting all is safe
- `fromGrantedScopeString(string)` — filters to entries whose scopes overlap the token's granted scopes (read from `hmrc_scope` session key)
- `fromSubscriptions(array)` — filters by the HMRC Developer Hub subscriptions endpoint response
- `routeFor(string)` — maps context path → named route (`backend/hmrc/vatObligations`, `backend/hmrc/selfEmploymentBusinesses`, or `null`)

---

## Scope discovery strategy

HMRC publishes no public endpoint listing which APIs a given `client_id` is subscribed to.
Three-tier fallback:

1. **Post-login** — parse `hmrc_scope` session value (set by the callback after token exchange); most precise
2. **Pre-login** — call `GET https://developer.service.hmrc.gov.uk/developer/api/applications/{clientId}/subscriptions`; may require developer portal auth; fails gracefully to `[]`
3. **Always visible** — `HmrcApiCatalogue::all()` full catalogue shown in a card on `/backend/hmrc` with green row highlighting for granted entries

---

## `/backend/hmrc` dashboard (`HmrcController::index()`)

Status card shows: VRN, FPH connection method, vendor product/version, granted scopes (when authenticated with HMRC).

Available APIs card:
- Shows `✅ Subscriptions loaded from HMRC` / `Derived from granted scopes` / **"Log in with HMRC"** button depending on state
- Dropdown (`<select data-route>`) lists APIs that are available; "Go →" navigates to the relevant controller action
- Scope reference table below the dropdown

Full API Catalogue card (always visible): all eight entries; rows highlighted green when within the current granted token scope; ✅ in Route column where a dedicated route exists.

---

## OAuth flow for authenticated admins

### Problem (before fix)

`callbackDeveloperGovSandboxHmrc` always ran `oauthRegisterAndProceed`, which either:
- Switched the session to the HMRC test user (logging out the admin), or  
- Showed a "proceed" view requiring a second button click, then redirected to `site/index`

Neither path returned the admin to `backend/hmrc` with their session intact.

### Fix (`src/Auth/Trait/Callback.php`)

After storing the five HMRC tokens in the session (`hmrc_access_token`, `hmrc_token_type`, `hmrc_token_expires`, `hmrc_scope`, `hmrc_refresh_token`), check whether the Yii3-i user is already authenticated:

```php
if ($this->authService->getIdentity()->getId() !== null) {
    $response = $this->webService->getRedirectResponse('backend/hmrc/index');
}
```

If authenticated: redirect immediately to `backend/hmrc`. The admin stays logged in; the HMRC tokens are in the session for API calls. `createTestUserIndividual` and `oauthRegisterAndProceed` are skipped entirely.

If not authenticated (e.g., a fresh login-page flow for an HMRC-only user): the original `oauthRegisterAndProceed` path continues unchanged.

---

## HMRC login button on `/backend/hmrc`

`HmrcController::index()` injects `DeveloperSandboxHmrc` and `UrlGeneratorInterface`.
When `$developerSandboxHmrc->getClientId() !== ''`, it generates:

```
/authclient?authclient=developersandboxhmrc
```

and passes it to the view as `$hmrcAuthUrl`. The view renders a `<a class="btn btn-sm btn-dark">Log in with HMRC</a>` in the Available APIs card header. This routes through `AuthController::authclient()`, which sets PKCE `code_verifier`/`code_challenge` in the session and redirects to HMRC's authorization endpoint.

---

## Login page — HMRC button removed

Previously a "Continue with Developer Gov Sandbox UK" button appeared on the login page (`/login`) controlled by the setting `no_developer_sandbox_hmrc_continue_button`. This button initiated the same HMRC OAuth flow but for anonymous users, creating a low-privilege `hmrcXXXXX` observer account in Yii3-i — a confusing user experience with no practical benefit.

Removed in full:

| Location | Change |
|---|---|
| `src/Auth/Trait/Oauth2.php` — `idpList()` | Deleted `developersandboxhmrc` entry and `$noDeveloperSandboxHmrcContinueButton` variable |
| `partial_settings_oauth2.php` | Deleted `$kNoHmrc` variable and HMRC checkbox block |
| `src/Invoice/Trait/InvoiceInstallTrait.php` | Deleted `no_developer_sandbox_hmrc_continue_button => 1` install default |
| `src/Invoice/UserInv/UserInvController.php` | Reverted dead HMRC-specific redirect in `signup()`; removed `AuthController` import |

The only entry point for HMRC OAuth is now the **"Log in with HMRC"** button on `/backend/hmrc`, which requires the admin to already be authenticated.

---

## Key session values set by the callback

| Key | Example value | Purpose |
|---|---|---|
| `hmrc_access_token` | `476425f97e53ca1124161e491bee384e` | Bearer token for API calls |
| `hmrc_token_type` | `bearer` | Token type |
| `hmrc_token_expires` | `time() + 14400` | Expiry timestamp |
| `hmrc_scope` | `read:vat write:vat read:self-assessment` | Drives `HmrcApiCatalogue::fromGrantedScopeString()` |
| `hmrc_refresh_token` | `cbe7c4f01a6bc55034237718d3e4ded2` | For token refresh (not yet wired) |

---

## Files changed (this session)

| File | Change |
|---|---|
| `src/Auth/Client/HmrcApiCatalogue.php` | New — 8-entry catalogue, scope helpers, route map |
| `src/Auth/Client/DeveloperSandboxHmrc.php` | `getDefaultScope()` → `HmrcApiCatalogue::allScopes()`; `getClientId()` exposed |
| `src/Backend/Controller/HmrcController.php` | `DeveloperSandboxHmrc` + `UrlGeneratorInterface` injected; `hmrcAuthUrl` + `fullCatalogue` passed to view |
| `src/Auth/Trait/Callback.php` | Early return to `backend/hmrc` when admin already authenticated |
| `src/Auth/Trait/Oauth2.php` | `developersandboxhmrc` removed from `idpList()` |
| `resources/backend/views/hmrc/index.php` | Full catalogue card; Available APIs dropdown; real OAuth button |
| `resources/backend/views/hmrc/selfEmploymentBusinesses.php` | New — self-employment business list view |
| `resources/views/invoice/setting/views/partial_settings_oauth2.php` | HMRC checkbox block removed |
| `src/Invoice/Trait/InvoiceInstallTrait.php` | Install default for removed setting deleted |
| `config/common/routes/routes-backend.php` | `backend/hmrc/selfEmploymentBusinesses` route added |

---

## First real live sandbox test — 3 bugs found (September 2026)

This whole flow had never been exercised against a real HMRC sandbox test
user until now — confirmed via `createTestUserIndividual()`'s own
docblock ("Not tested yet 23/05/2025"). Created a real Organisation test
user via HMRC's standalone `developer.service.hmrc.gov.uk/api-test-user`
page (VAT enrolment ticked, generating a real VRN — the app's own
`createTestUserIndividual()` couldn't be used for this: it's
application-restricted per HMRC's own docs and needs a `server_token`
this app has never collected, gated behind requiring a user OAuth token
that in turn requires a test user to already exist — a chicken-and-egg
gap, not fixed this pass). Signed in via "Log in with HMRC" using that
test user's Government Gateway credentials, saved its VRN into
Settings → Making Tax Digital, then exercised the dropdown.

Three real bugs found and fixed, all confirmed against HMRC's own
published OAS specs rather than guessed:

1. **VAT Obligations 401 regardless of test user/VRN correctness.** Root
   cause: `vatObligations()`/`vatReturnSubmit()` hardcoded the
   **production** host (`api.service.hmrc.gov.uk`) directly, while the
   OAuth login (correctly) authenticates against the **sandbox** host
   (`test-api.service.hmrc.gov.uk`) — a sandbox-issued token sent to
   production always 401s. `DeveloperSandboxHmrc::getApiBaseUrl1()`
   already resolves the right host per environment, but
   `setEnvironment()` is only ever called from
   `AuthController`/`SignupController`
   (`Oauth2::initializeOauth2IdentityProviderDualUrls()`) — on the
   later, separate `/backend/hmrc/vatObligations` request, that never
   ran, so the injected `DeveloperSandboxHmrc` instance's environment was
   never actually set for this action. Fixed with a new
   `HmrcController::resolveHmrcApiBaseUrl()` that resolves the
   environment itself from `SettingRepository::getEnv()` on every call,
   rather than assuming an earlier request left it in the right state.
   Also applied to `selfEmploymentBusinesses()` (previously hardcoded to
   sandbox unconditionally — correct only by accident today, wrong once
   this app ever points at real production data).
2. **"FPH Feedback (VAT)" 404 (`MATCHING_RESOURCE_NOT_FOUND`).**
   `fphFeedback()` POSTed; HMRC's real `txm-fph-validator-api` OAS spec
   says this endpoint is `GET`. It also passed a bare `vat` as the `{api}`
   path parameter; the spec's real enum requires each service's
   `-mtd`-suffixed identifier (`vat-mtd`). Fixed both, and added the
   `Authorization: Bearer` header this action was missing entirely
   (present on the sibling `fphValidate()` action for the same API
   family, absent here).
3. **"Test FPH Headers" `RESOURCE_FORBIDDEN`** ("The application is not
   subscribed to the API which it is attempting to invoke") — not a code
   bug: the registered application needs a separate Developer Hub API
   subscription for **Test Fraud Prevention Headers** (`txm-fph-validator-api`),
   distinct from the VAT (MTD) subscription already toggled on. A
   Developer Hub configuration step, not fixed in code.

**Re-confirmed live** after deploying the host fix: VAT Obligations now
returns a real HMRC sandbox canned response (period `18A1`,
2017-01-01–2017-03-31, due 2017-05-07, status `Open`) for VRN 931392528 —
the main bug above is genuinely fixed, not just plausible from reading the
code.

That same successful page surfaced a fourth bug, in the rendered table
itself: the "Prepare Return" button for an open obligation showed as raw,
HTML-escaped text (`&lt;a href="..."&gt;Prepare Return&lt;/a&gt;`) instead
of a clickable button. Root cause: `Yiisoft\Html\Html::tag()`'s `$content`
parameter only skips HTML-encoding for a `Stringable` that implements
`NoEncodeStringableInterface` (every `Yiisoft\Html\Tag\Base\Tag` subclass
does) — `vatObligations.php`'s Action column called `->render()` on the
`A` tag *before* passing it to the outer `H::tag('td', ...)`, turning it
into a plain `string` first, which then got encoded like any other
untrusted text. The adjacent Status column (a `Span` badge) never had this
bug — it passes the tag object directly, without rendering it first.
Fixed by dropping the stray `->render()` call. While already in the file
(never Psalm-checked before this session), also cleaned up 5 redundant
`(string)` casts the docblock's own typing already made unnecessary.

A fifth bug, found continuing the same live test through to the final
step: clicking "Submit VAT Return to HMRC" on the Box 1-9 form silently
redirected to the site homepage instead of submitting anything.
`vatReturnSubmit.php`'s `<form method="post">` had no `_csrf` hidden
field at all — `CsrfTokenMiddleware` (wired with this app's own
`CsrfFailureHandler`, `config/common/di/router.php`) rejects any
unsafe-method request missing a valid token and its failure handler
redirects to `site/index` unconditionally, exactly matching what was
seen. Fixed by adding `echo H::hiddenInput('_csrf', $csrf);` inside the
form, the same established pattern already used by every other
hand-built form in this app (`invoice/inv/trash.php`,
`invoice/salesorderitem/_item_edit_form.php`,
`invoice/setting/tab_index.php`, ...) — `$csrf` is ambiently available in
every view already, no controller change needed.
