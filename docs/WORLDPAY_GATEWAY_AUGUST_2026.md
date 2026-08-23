# Worldpay Gateway — August 2026

## Summary

Added **Worldpay** as this app's 16th payment gateway (`PaymentGatewayInterface`
driver key `worldpay`), built against Worldpay's **orchestration Payments API**
(`POST https://try.access.worldpay.com/api/payments`) + the **Access Checkout
SDK** (`checkout.js`) for client-side card tokenization. v1 scope: guest
one-off card payment (CIT) with mandatory 3DS, an HMAC-verified webhook, and
refund. CIT/MIT stored-card recurring billing is explicitly **not** built —
see "Deferred" below.

Worldpay is genuinely three separate API generations sharing one brand, easy
to conflate:

1. Legacy `api.worldpay.com/v1` (service-key auth) — not a contender.
2. **Card Payments API v7** (`cardPayments/customerInitiatedTransactions`) —
   initially picked as the target for its cleaner HATEOAS design, then
   **reversed**: confirmed Enterprise-tier only, not offered on the SMB
   self-serve signup track at all (`developer.worldpay.com`'s own "SMB
   (Worldpay eCommerce)" comparison page only lists Hosted Payment Pages or
   "SDK and Payments API" — v7 isn't one of the options), and its own docs
   say credentials come "from your Worldpay Implementation Manager."
3. **The orchestration Payments API** (what this app actually targets) —
   `x-metadata.business: ["Enterprise", "SMB (Worldpay eCommerce)"]` on the
   spec itself confirms SMB availability directly. Described in Worldpay's
   own docs as orchestrating "FraudSight, 3DS and Token creation" behind a
   *single* `POST /api/payments` call — confirmed genuinely true, not
   marketing: 3DS device-data-collection and the challenge are two more
   actions on the *same* payment resource
   (`.../3dsDeviceData`, `.../3dsChallenges`), not a call out to the
   separate, Enterprise-only, standalone 3DS API product.

There's also a genuinely separate, older **"Corporate Gateway" (CG)**
product line (`github.com/Worldpay/Worldpay-Magento2-CG`) — not related to
Access Worldpay, deliberately not used as a reference for anything here.

## Ground-truthing

Primary source: Worldpay's own docs (`docs.worldpay.com`), a JS-rendered SPA
that doesn't survive automated fetching — grounded via the user's own
browser (screenshots/pastes of real request/response examples, sequence
diagrams, and the account's live dashboard) rather than scraped. The full
Payments API OpenAPI spec (`worldpay-portal.eu.redocly.app/access/_bundle/products/payments/@20240601/openapi.yaml`,
~6,700 lines) was pulled directly and is the source for every field name/shape
cited below.

Confirmed directly from the spec + real docs pages:

- Headers: `Authorization: Basic base64(username:password)`,
  `Content-Type`/`Accept: application/json`, `WP-Api-Version: 2024-06-01`
  (content-negotiation versioning, not v7's vendor-media-type scheme).
- Request shape: `transactionReference` (own, invoice-correlatable) +
  `orderReference` + `merchant.entity` (`"default"` in every real example
  seen) + `instruction.{method: "card", settlement.auto: true,
  paymentInstrument, narrative.line1, value.{currency, amount},
  threeDS.challenge.{returnUrl, preference}}`.
- `paymentInstrument.type` for the Checkout SDK session is bare
  **`"checkout"`** (not `"card/checkout"` — that's a different, unrelated
  product's discriminator, Verified Tokens) + `sessionHref` +
  `cardHolderName` + `billingAddress`.
- Response is a discriminated union on `outcome`: `authorized` /
  `3dsDeviceDataRequired` / `fraudHighRisk` / `refused` (201), or
  `sentForSettlement` / `sentForCancellation` (202) — each carrying its own
  `_links.self.href` (the query URL) and `_actions` (state-dependent next
  steps — e.g. no refund action exists until the payment has actually
  settled).
- Full manage-payments endpoint map: `GET /api/payments/{linkData}`
  (`queryEvents`), `.../settlements`, `.../partialSettlements`,
  `.../refunds`, `.../partialRefunds`, `.../cancellations`, `.../reversals`.
- Webhooks (`docs.worldpay.com/access/products/events/*`): `Event-Signature:
  {keyId}/{hashFunction}/{signature}` HMAC-SHA256 — **can contain multiple
  comma-separated entries** (key-rotation window), order not guaranteed, so
  verification must try every entry against the one configured secret, not
  assume a single triple. `200` ack required within 10s, retries escalate
  30s→2h, stop after `200` or one week. IP allowlist and full event
  vocabulary confirmed from the same pages — `settled` is the mark-paid
  trigger. The Events product's own metadata is `business: ["Enterprise"]`
  only (unlike the core Payments API) — go-live needs at least one human
  touchpoint for webhook setup even in the best case.
- 3DS is built on **Cardinal Commerce** (confirmed twice from real example
  URLs: `centinelapistag.cardinalcommerce.com` for both the DDC Collect and
  StepUp challenge endpoints, test environment).

**Cross-checked against two official Worldpay reference implementations**
(read directly for research purposes only — not installed, different
platforms/PHP versions from this app): the Magento `EmbeddedCheckout` plugin
(`github.com/Worldpay/Worldpay-Ecommerce`, v1.1.5) and the WooCommerce
plugin from the same repo (v1.3.3). Both bundle the identical
`worldpay/php-sdk` composer package (`diff` confirmed the relevant SDK files
are byte-for-byte identical between the two plugins), so this isn't
platform-specific plumbing — it's Worldpay's universal client behaviour.
This caught and fixed several real bugs found only after cross-checking
against real code, not docs:

- The session call is **`checkout.generateSessionState()`**, not
  `generateSessions()` — returns a single combined `paymentSession` string
  (not separate card/cvv sessions), confirmed to map directly onto
  `paymentInstrument.sessionHref` with zero transformation server-side.
- The Device Data Collection form needs a **`Bin`** field (capital B)
  alongside `JWT` — confirmed from the SDK's own
  `Forms\DeviceDataCollection::render()`.
- The DDC `postMessage` payload is a **JSON string** (needs `JSON.parse`),
  shaped `{MessageType: "profile.completed", Status: true, SessionId:
  "..."}` — `SessionId` is the `collectionReference`.
- The challenge form needs an **`MD`** field alongside `JWT` (Worldpay's own
  SDK default is `''` when there's no merchant correlation data to carry).
- **The 3DS challenge return does NOT escape the iframe on its own.** Both
  reference implementations' `ThreeDsChallenge/Submit` handlers render, at
  the exact URL passed as `threeDS.challenge.returnUrl`, a tiny HTML
  fragment whose only job is dispatching a `CustomEvent` to `window.parent`
  — confirming the ACS genuinely navigates/POSTs **the still-open iframe
  itself**, not the top-level page. This app's `worldpayComplete` route
  renders a full page instead of a lightweight relay fragment, so it needs
  (and now has) an explicit escape-to-top-level step.
- `challenge.payload` (a base64 JSON blob) carries the issuer's own
  recommended `challengeWindowSize` code (`'01'`-`'05'`) — now parsed to
  size the challenge iframe correctly instead of a hardcoded guess.

## Architecture

Matches the established 15-gateway conventions exactly, with one new
structural piece:

- `WorldpayPaymentService` implements `PaymentGatewayInterface` via direct
  Guzzle (no official Worldpay PHP SDK exists as an installable package —
  same situation as Paystack/Razorpay/YooKassa/Robokassa).
- **New per-provider entity, `WorldpayMerchant`** (mirrors `SquareMerchant`
  — see `SQUARE_MERCHANT_PER_PROVIDER_ENTITY_AUGUST_2026.md`), needed
  because Worldpay's `_links.self.href`/`_actions.*.href` values are long
  opaque HATEOAS tokens the generic `Merchant.provider_reference`
  `string(151)` column can't safely hold, and `verifyPayment()`/`refund()`
  need that href directly (state-dependent — refund is only discoverable by
  re-`GET`-ing the payment, never a fixed URL). Also holds
  `pending_action_href` — whatever the *current* 3DS step's next action is,
  since those are opaque per-step hrefs too, not reconstructible by string
  concatenation from `self_href`.
- **`WorldpayPaymentController` is architecturally new for this app**: every
  other gateway either hands off to the provider's own hosted
  page/Drop-in entirely, or does a single client-side tokenize-then-submit
  step. Worldpay's Checkout SDK + mandatory 3DS needs real AJAX round-trips
  between this app's own frontend and backend mid-payment (create payment →
  maybe supply device data → maybe complete a challenge) — three new
  endpoints (`worldpayCreatePayment`, `worldpaySupply3dsDeviceData`,
  `worldpayComplete`) instead of the usual two (`*InForm`, `*Complete`).
- Persistence timing also differs from every other gateway: a **provisional**
  `WorldpayMerchant` row is written synchronously right after a successful
  `createPayment()` call — before this app can know the final outcome —
  because the HATEOAS `self_href` can't be reconstructed later from
  anything a webhook payload carries. `WorldpayWebhookHandler` is what
  flips that row to confirmed on a verified `settled` event (re-confirms via
  `verifyPayment()` before doing so — the same belt-and-braces pattern used
  for every gateway without a signature, applied here even though Worldpay
  does have one).
- `payment-worldpay.ts` builds the DDC/challenge hidden-iframe-form-POST
  mechanics directly in client-side JS. Worldpay's own reference
  implementations instead point an iframe at a small **server-rendered
  bridge page** that renders the same form server-side — functionally
  equivalent (same fields, same target, same end result), just a different
  place to build the HTML. Not changed to match; noted as a validated
  alternative.
- Settings: `username`/`password` (Basic Auth, from the dashboard's
  Credentials tab), `entity` (text, not password — `"default"` in every
  real example), `tradingName` (text — **not** sourced from Worldpay at
  all, it's `narrative.line1`, the customer's statement descriptor, freely
  chosen), `webhookSecret`, `sandbox`.

## Deferred, by design

CIT/MIT stored-card recurring billing (`customerAgreement`/`tokenCreation`)
— confirmed no existing entity fits (checked `InvRecurring`: it's pure
invoice-generation scheduling, `inv_id`/`start`/`end`/`frequency`/`next`,
zero token-storage fields despite the name suggesting otherwise; not
`Payment` or `Merchant` either). Building fresh schema for this is a
separate, later decision.

## Untested against a real account — status

A live signup produced a real dashboard account: `Business Type: Enterprise
Merchant`, `On Boarding Status: NOT_BOARDED`, `Role: Prospect` — but the
**Credentials tab shows a real, ACTIVE Try-environment credential**
available immediately (`Try Now` action present), so sandbox testing is not
blocked by the account's boarding status. Best-supported read: `NOT_BOARDED`
gates live/production go-live specifically, not the Try environment.

Genuinely still open, resolvable only by live testing, not more reading:

- No live payment has been made yet. The general card-testing magic values
  (`docs.worldpay.com/products/card-payments/testing`) are confirmed **not**
  sufficient on their own — this app always requests a 3DS challenge
  (`threeDS.challenge.preference: "challengeRequested"`), and the docs
  explicitly warn plain test cards will fail to load a challenge. The
  **3DS-specific test card numbers**
  (`docs.worldpay.com/products/3ds/testing`) are needed for a real
  end-to-end run.
- Whether the dashboard's self-serve Events → Notifications tab actually
  lets you register this app's webhook URL
  (`/paymentinformation/worldpayWebhook`) and returns a shared secret, or
  routes to "contact your Implementation Manager" instead — the docs and
  the live dashboard UI genuinely disagree on this, not yet resolved either
  way.
- The live (non-`try.`) Cardinal Commerce host for the CSP `frame-src`/
  `child-src` entries — only the test host is in there currently, since no
  live challenge has been exercised yet to observe it.
- `gatewayCredentialUrls()['worldpay']` is set to
  `https://docs.worldpay.com/dashboard` (user-confirmed).

## Verification

- `vendor/bin/psalm --no-cache` — zero errors on every new/changed file.
- `npx tsc --noEmit` and the real `npm run build:typescript:prod` esbuild
  pipeline — both clean.
- `vendor/bin/testo --suite=Unit` — 932/932 passed (includes 3 new Worldpay
  Testo files: `WorldpayMerchantTest`, `WorldpaySignatureServiceTest`,
  `WorldpayPaymentServiceTest`).
- `vendor/bin/phpunit --testsuite=Unit` — 3850/3850 passed (includes
  `WorldpayWebhookHandlerTest`, 7 cases, confirmed independently
  notice-free). Caught and fixed a real pre-existing gap this surfaced:
  `PaymentRefundControllerTest`'s hardcoded positional constructor call
  hadn't been updated for the new params.
- **Found, not fixed here (out of scope for a gateway addition)**: the full
  PHPUnit suite carries 3 notices, 2 confirmed (tested each file in
  isolation) to pre-exist in `AdyenWebhookHandlerTest`/
  `StripeWebhookHandlerTest`, unrelated to this work — CLAUDE.md's "the
  suite is notice-free as of August 2026" claim is currently stale.
