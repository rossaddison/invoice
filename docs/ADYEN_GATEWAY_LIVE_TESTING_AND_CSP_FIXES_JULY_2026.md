# Adyen Payment Gateway — Live Testing & Cross-Gateway CSP Fixes — July 2026

## Why

Follow-up to [Payment Gateway Live Testing — Real Bugs Found Only Under
End-to-End Testing](PAYMENT_GATEWAY_LIVE_TESTING_JULY_2026.md). Adyen was
added as a fifth PCI-compliant gateway (`AdyenPaymentController`,
`AdyenPaymentService`, `AdyenWebhookHandler`/`AdyenWebhookContext`,
`payment-adyen.ts`, `AdyenAsset`) and driven live end-to-end via the browser
— session creation → Drop-in render → card/bank/Paysafecard payment methods
— surfacing both Adyen-specific config gaps and a batch of pre-existing CSP
domain gaps across Amazon Pay and Braintree that only show up once the
browser console is actually watched during a real payment flow, not from
static review.

## Adyen — external config, not code

- **`gateway_adyen_merchantAccount` had a typo** (`RossAddisonServicesECON`
  instead of `...ECOM`). Adyen's sessions API returned a real, correctly
  HMAC-authenticated `"Invalid Merchant Account"` error — confirmed via
  `runtime/logs/app.log`, which also showed the error message evolving
  through `HTTP Status Response - Unauthorized` → `Field 'merchantAccount'
  is not valid.` → `Invalid Merchant Account` across earlier attempts as the
  API key and merchant account were filled in one at a time. Not a code
  defect; outbound HTTPS from local WAMP to Adyen's API worked correctly the
  whole time, ruling out the local-SSL-certificate class of problem this was
  initially suspected to be.
- **Adyen Client Key CORS allowlist** — the Drop-in component's own preflight
  (OPTIONS) requests to `checkoutshopper-test.adyen.com` (`setup`, `id`,
  `log` endpoints) returned `403 Forbidden` even with a valid, non-empty
  Client Key. Root cause: Adyen Client Keys carry an explicit **Allowed
  Origins** allowlist configured in the Adyen Customer Area (Developers → API
  credentials → Client key settings), and `http://localhost` was never
  registered there. This is the actual "Adyen cannot test locally" blocker
  reported at the start of this session — fixed by adding `http://localhost`
  to Allowed Origins (Adyen accepts plain `http://` only for
  localhost/loopback test origins). No code change.

## CSP gaps found live (browser console, not static review)

Both `config/web/params.php` (`csp.policy`) and the mirrored
`Content-Security-Policy` header in `public/.htaccess` must stay in sync —
browsers apply the *intersection* of both headers, so a domain missing from
either one still blocks the request. All fixes below were applied to both
files.

- **`img-src` missing `*.adyen.com` / `*.cdn.adyen.com`.** `script-src`,
  `style-src`, `connect-src`, and `frame-src` already had the shared
  `$adyenCsp` constant; `img-src` never got it, so the Drop-in's
  payment-method icons were silently blocked.
- **`img-src` missing `*.media-amazon.com`.** Amazon Pay's logo
  (`m.media-amazon.com/images/G/02/AmazonPay/...`) is served from Amazon's
  general media CDN — a different domain entirely from
  `*.payments-amazon.com` (which the prior live-testing pass already fixed
  for `img-src`, covering the SDK/button-graphics/checkout-iframe domain
  only).
- **`connect-src` missing Amazon's regional payments API domain.** The
  Amazon Pay SDK's promotional-messaging fetch
  (`payments-eu.amazon.com/promotionalMicrotextMessage?...`) hits a
  region-specific `payments-{eu,na,fe}.amazon.com` subdomain, not
  `*.payments-amazon.com`. First fix attempt used
  `https://payments-*.amazon.com` — **invalid CSP syntax**: a host-source
  wildcard can only replace an entire dot-separated label (`*.amazon.com`),
  not a partial label (`payments-*.amazon.com`), and browsers silently treat
  an invalid source as non-matching rather than erroring, so the fetch stayed
  blocked. Corrected to `*.amazon.com` and folded into the shared
  `$amazonPayCsp` constant (used across script/img/connect/frame-src)
  despite being broader than strictly necessary.
- **`connect-src` missing `*.braintree-api.com`.** Braintree Drop-in v3's
  client-side tokenization GraphQL API lives on `payments.braintree-api.com`
  / `payments.sandbox.braintree-api.com` — a completely separate second-level
  domain from `*.braintreegateway.com`, which was the only Braintree domain
  present. This gateway has no PayPal/Venmo/Google Pay configured
  (`BraintreePaymentService::generateClientToken()` requests no additional
  payment methods), so this was the sole remaining gap for plain card
  tokenization.
- **Stripe — no CSP or code issues.** Remaining console output
  (`Stripe.js` HTTP-testing notice, Payment Element API migration
  suggestion, Apple Pay/Google Pay requiring HTTPS, unregistered domain for
  Apple Pay) is entirely informational and inherent to testing over
  `http://localhost` — none of it is a CSP violation or actionable code
  defect.

## Non-CSP code fixes found along the way

Surfaced by the same live browser-console pass; unrelated to Adyen/Amazon/
Braintree specifically but caught because the console was being watched end
to end.

- **BACS quick-pay modal's inline `<script>` blocked by `script-src`.**
  `_modal_bacs_quickpay.php` (rendered on the guest invoice list whenever
  `bacsPaymentService->isCompanyPrivateActive()`) initialised ClipboardJS via
  `Html::script(...)` — still an inline `<script>` block with no
  nonce/hash, so `script-src 'self'` (no `unsafe-inline`) blocked it
  regardless of the `Html::script()` wrapper. Moved to a new
  `src/typescript/bacs-quickpay.ts` (`initBacsQuickPay()`), wired into
  `index.ts` alongside the other `init*Payment()` calls, matching the
  existing data-driven migration pattern (`payment-adyen.ts`,
  `payment-braintree.ts`).
- **bs5-lightbox vs. Bootstrap asset load order on guest-facing pages.**
  `node_modules/bs5-lightbox/dist/index.bundle.min.js` reads
  `window.bootstrap.Modal`/`.Carousel` synchronously at script-parse time
  (`var s=window.bootstrap; const a={Modal:s.Modal,...}`). `guest.php`
  registers `InvoiceNodeModulesAsset`/`InvoiceCdnAsset` (which pull in the
  lightbox asset as a dependency) *before* registering the Bootstrap asset —
  the reverse of `invoice.php`'s order, which has an explicit comment about
  why Bootstrap must go first. Yii's `AssetManager` inserts a bundle's
  dependencies into the output queue at the moment the *dependent* bundle is
  registered, so on every guest-facing page (guest invoice list, the Adyen
  payment page, etc.) the lightbox script was queued ahead of
  `bootstrap.bundle.js`, throwing `TypeError: Cannot read properties of
  undefined (reading 'Modal')`. Fixed at the asset-dependency level rather
  than reordering the two `register()` calls in `guest.php`: both
  `NodeModulesBootstrapLightboxAsset` and `Bootstrap5LightBoxCdnAsset` now
  declare `BootstrapJsOnlyAsset`/`BootstrapCdnJsOnlyAsset` in their own
  `$depends`, which `AssetManager` always resolves correctly regardless of
  layout registration order — more robust than relying on manual ordering
  that had already silently drifted out of sync once.

## Summary

| Area | Fix | Type |
|---|---|---|
| Adyen merchant account | Corrected typo (`ECON` → `ECOM`) | External config |
| Adyen Client Key CORS | Registered `http://localhost` in Adyen Customer Area | External config |
| CSP `img-src` | Added `*.adyen.com` / `*.cdn.adyen.com` | Code (`params.php` + `.htaccess`) |
| CSP `img-src` | Added `*.media-amazon.com` | Code (`params.php` + `.htaccess`) |
| CSP `connect-src` | Added `*.amazon.com` (regional Amazon Pay API) | Code (`params.php` + `.htaccess`) |
| CSP `connect-src` | Added `*.braintree-api.com` | Code (`params.php` + `.htaccess`) |
| BACS quick-pay inline script | Moved to `bacs-quickpay.ts` | Code |
| bs5-lightbox/Bootstrap load order | Explicit asset `$depends` | Code |
| Stripe console warnings | None needed — informational only | N/A |
