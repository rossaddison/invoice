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
