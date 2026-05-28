# BACS Quick Pay

## Overview

Added a BACS (Bankers' Automated Clearing Services) **credit-transfer payment option** to the
invoice guest page. This is a one-off bank-transfer flow — the customer pushes money using
displayed sort code, account number and a QR code; no Direct Debit mandate is required.

---

## Files added

| File | Purpose |
|------|---------|
| `resources/views/invoice/inv/_modal_bacs_quickpay.php` | Bootstrap 5 modal — bank details, per-invoice QR codes, copy-to-clipboard |
| `src/Invoice/PaymentInformation/Service/BacsPaymentService.php` | Service: reads BACS settings from `CompanyPrivate`, builds QR content, renders data URI |
| `Tests/PHPUnit/BacsPaymentServiceTest.php` | 23 PHPUnit tests for the service |
| `Tests/PHPUnit/PciAssetTest.php` | 8 PHPUnit tests asserting `POSITION_HEAD` and `https://` on all PCI asset bundles |
| `docs/PCI_GATEWAY_ASSET_LOADING.md` | Companion doc covering the gateway asset ordering fix |

## Files modified

| File | Change |
|------|--------|
| `src/Infrastructure/Persistence/CompanyPrivate/CompanyPrivate.php` | Added `bacs_sort_code` and `bacs_account_number` fields with getters/setters |
| `src/Invoice/Inv/Trait/Guest.php` | Populated `$bacsUnpaidInvs` and passed it to the modal partial |
| `resources/views/invoice/inv/guest.php` | Wired the modal trigger button and rendered `$modalBacsQuickPay` |
| `resources/messages/en/app.php` | Added `bacs.*` translation keys |
| `src/Invoice/Asset/pciAsset/StripeVersionTenAsset.php` | `jsPosition = POSITION_HEAD`; `https://` CDN URL |
| `src/Invoice/Asset/pciAsset/BraintreeDropInOneThirtyThreeSevenAsset.php` | `jsPosition = POSITION_HEAD`; `https://` CDN URLs (JS + CSS) |
| `src/Invoice/Asset/pciAsset/AmazonPayTwoSevenAsset.php` | `jsPosition = POSITION_HEAD` |
| `Tests/Unit/Invoice/Entity/CompanyPrivateEntityTest.php` | Added 7 BACS field tests |

---

## Issues found and fixed during the session

### 1 — Stripe/Braintree/Amazon Pay JS not initialising

**Symptom:** Opening the Stripe payment URL showed the form shell but Stripe Elements
never mounted inside `#payment-element`.

**Root cause:** Yii3's asset manager outputs JS bundles in registration order at
`POSITION_END`. `InvoiceNodeModulesAsset` (containing the IIFE bundle) was registered
before the PCI gateway bundles, so `invoice-typescript-iife.js` executed first.
At that point `//js.stripe.com/v3/` had not yet run and the `Stripe` global was
undefined — `initStripePayment()` failed silently.

**Fix:** Set `public ?int $jsPosition = WebView::POSITION_HEAD` on all three PCI asset
bundles. Head scripts always execute before end-of-body scripts regardless of
registration order.

### 2 — CSP violations (Stripe, Braintree)

**Symptom:** Browser console errors:
```
Loading the script 'http://js.stripe.com/v3/' violates CSP directive "script-src ... https://js.stripe.com ..."
Loading the stylesheet 'http://assets.braintreegateway.com/...' violates CSP directive "style-src ... https://assets.braintreegateway.com ..."
```

**Root cause:** Protocol-relative URLs (`//js.stripe.com/v3/`) resolve to `http://` on
`http://localhost`. The CSP allowlist entries specify `https://` only.

**Fix:** Changed all CDN URLs in PCI asset classes to explicit `https://`.

### 3 — Modal showed "no outstanding invoices" for every customer

**Symptom:** The BACS modal always rendered the "no invoices" fallback even when the
customer had unpaid invoices.

**Root cause:** `Guest.php` trait called `$iR->repoUnpaidByClientIds($user_clients)` and
stored the result in `$bacsUnpaidInvs`, but the subsequent `renderPartialAsString` call for
`_modal_bacs_quickpay` only passed `bacsPaymentService` and `decimalPlaces` — `bacsUnpaidInvs`
was omitted.

**Fix:** Added `'bacsUnpaidInvs' => $bacsUnpaidInvs` to the `renderPartialAsString`
parameter array.

### 4 — Psalm errors on view file

**Symptom:** Running `vendor/bin/psalm` raised `UndefinedGlobalVariable`,
`UnnecessaryVarAnnotation`, `RedundantCondition`, and `InvalidScope` on the modal view.

**Root cause:** Psalm's `UnnecessaryVarAnnotation` fires for grouped `@var` docblocks
whose types it can already infer in full-project mode. Several attempts at inline `??`,
`assert()`, and `$this->registerJs()` introduced their own contradictions.

**Fix:**
- Kept the conventional grouped `@var` docblock (required for IDE and human readers).
- Replaced `$this->registerJs()` with `echo H::script(...)` inline in the view body
  (avoids `InvalidScope` without suppression).
- Removed all `??`/`assert()` tricks — Psalm infers the types from the full project in
  its authoritative full-project run.
- Added `#[\Override]` to `CompanyPrivateRepository::repoCompanyPrivateActive()` after
  brief interface experiment was reverted (see below).

### 5 — `CompanyPrivateRepositoryInterface` introduced then reverted

**Introduced** to allow mocking the `final` `CompanyPrivateRepository` in unit tests.
**Reverted** after the user confirmed the project does not normally interface repositories.

**Resolution:** `BacsPaymentServiceTest` bypasses the `final` constructor using
`ReflectionClass::newInstanceWithoutConstructor()` and injects a `createStub(Select::class)`
directly into the parent `Select\Repository::$select` property via reflection.
`Select` (Cycle ORM) is not `final` and is safely stubbable.

---

## Modal features

- **Bank details card** — payee name, sort code (formatted `XX-XX-XX`), account number,
  each with a borderless copy-to-clipboard icon button.
- **Per-invoice cards** — invoice number, balance (with currency symbol), payment reference,
  copy buttons, and a QR code the customer can scan with their mobile banking app.
- **QR code hint** — two-line label under each QR code explaining that scanning pre-fills
  payee, sort code, account number and amount in the customer's banking app.
- **Copy feedback** — clipboard icon flashes to `bi-clipboard-check` in green for 1.5 s
  after a successful copy, then reverts. Powered by ClipboardJS (already in the bundle).
- **Not configured** — graceful fallback message if no active `CompanyPrivate` record has
  BACS fields set.

---

## Copy button styling decisions

| Stage | Class | Reason |
|-------|-------|--------|
| Initial | `btn btn-sm btn-outline-secondary ms-2` | First pass — functional but visually heavy |
| Final | `btn btn-link p-0 ms-2 text-muted lh-1` | Borderless, zero-padding, muted icon; `lh-1` prevents affecting row height |

Icon locked to `fs-6` so it stays compact regardless of surrounding `fw-bold fs-5` text.

---

## Test summary

```
Tests/PHPUnit/BacsPaymentServiceTest.php   — 23 tests, 95 assertions
Tests/PHPUnit/PciAssetTest.php             —  8 tests, 15 assertions
Tests/Unit/Invoice/Entity/
  CompanyPrivateEntityTest.php             — 7 new tests (BACS fields)
```

All 81 tests pass, no PHPUnit notices, Psalm error level 1 clean on full project run.
