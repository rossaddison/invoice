# PCI Payment Gateway Asset Loading

## Problem

After the performance improvement that isolated payment gateway JavaScript
(Stripe, Braintree, Amazon Pay) to only initialise on their specific payment
pages, two related bugs appeared:

### 1. Stripe Elements not mounting (`initStripePayment` silent no-op)

`invoice-typescript-iife.js` is registered via `InvoiceNodeModulesAsset`,
which is added to the Yii3 asset manager before the PCI gateway bundles
(`StripeVersionTenAsset`, `BraintreeDropInOneThirtyThreeSevenAsset`).
Because neither bundle declares a dependency on the other, the asset manager
outputs them in registration order — both at `POSITION_END` (bottom of body).

Result: the IIFE executes first. By the time it calls `initStripePayment()`,
the browser has not yet fetched or run `//js.stripe.com/v3/`, so the `Stripe`
global is undefined and the call fails silently.

The same race condition applies to Braintree and Amazon Pay.

### 2. Content Security Policy violations

The PCI asset bundles used protocol-relative URLs (`//js.stripe.com/v3/`,
`//assets.braintreegateway.com/...`). On `https://` production servers these
resolve correctly to `https://`. On `http://localhost` they resolve to `http://`,
which is rejected by the CSP directives that list only the `https://` origins:

```
script-src ... https://js.stripe.com https://*.stripe.com ...
style-src  ... https://assets.braintreegateway.com ...
```

## Fix

### `jsPosition = POSITION_HEAD`

Setting `public ?int $jsPosition = WebView::POSITION_HEAD` on each PCI asset
bundle moves those CDN `<script>` tags from the bottom of `<body>` into
`<head>`. The browser fetches and executes them before the IIFE script tag is
reached, so `Stripe`, `braintree`, and the Amazon Pay global are all defined
when the IIFE runs.

### Explicit `https://` URLs

All protocol-relative URLs replaced with explicit `https://` in each PCI
asset bundle so CSP enforcement is consistent between localhost and production.

## Files changed

| File | Change |
|------|--------|
| `src/Invoice/Asset/pciAsset/StripeVersionTenAsset.php` | `https://js.stripe.com/v3/` · `jsPosition = POSITION_HEAD` |
| `src/Invoice/Asset/pciAsset/BraintreeDropInOneThirtyThreeSevenAsset.php` | `https://` URLs for JS + CSS · `jsPosition = POSITION_HEAD` |
| `src/Invoice/Asset/pciAsset/AmazonPayTwoSevenAsset.php` | `jsPosition = POSITION_HEAD` |

## Background: Yii3 asset output order

Yii3 resolves asset bundles topologically by their `$depends` array.
Bundles with no dependency relationship are output in the order they were
registered. `InvoiceNodeModulesAsset` is registered in `guest.php` before the
PCI bundles, so its JS (the IIFE) was always emitted first at `POSITION_END`.

Moving PCI bundles to `POSITION_HEAD` breaks the ordering dependency entirely:
head scripts always precede end-of-body scripts regardless of registration order.

## TypeScript isolation

`initStripePayment()`, `initBraintreePayment()`, and `initAmazonPayment()` each
guard their entry point with a check for a gateway-specific config element:

```typescript
const configEl = document.getElementById('stripe-payment-config');
if (!configEl) return; // not a Stripe payment page
```

This means the IIFE can safely import all three payment modules without
initialising any gateway SDK on non-payment pages — the performance isolation
is purely at the JavaScript level. Loading the CDN scripts in `<head>` adds
a small overhead on all guest pages; the follow-on task is to register the PCI
asset bundles only in their specific view files rather than in `guest.php`.
