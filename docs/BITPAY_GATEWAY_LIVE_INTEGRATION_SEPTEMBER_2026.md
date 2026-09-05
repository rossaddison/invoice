# BitPay Gateway — Live Integration, September 2026

This app's first cryptocurrency (Bitcoin and other chains) payment gateway,
and its first genuinely non-fiat payment method — built end to end in one
session, from "not started" to a real, live-confirmed payment automatically
marking an invoice paid, via 8 merged PRs (`rossaddison/invoice#1225`
through `#1232`) plus a standalone client repository built the prior
session and patched twice during this one.

## Why a hand-written client, not BitPay's own SDK

BitPay's official `bitpay/sdk` requires `symfony/console ^7.3.1` at most
across its entire published version history; this app pins
`symfony/console`/`symfony/process` to `>=8.1.6` with no ceiling — a
genuine, unresolvable Composer conflict, confirmed via
`composer require bitpay/sdk --dry-run --with-all-dependencies` (checked
directly, not assumed). BitPay's **POS facade** — the token-authenticated
part of its API that doesn't need the SDK's ECDSA client-identity
key-pairing — is a small enough surface (create/get invoice, verify a
webhook signature) that hand-writing it in
[`rossaddison/bitpay-client`](https://github.com/rossaddison/bitpay-client)
was less work than fighting the dependency conflict. Same precedent as
`rossaddison/storecove-client`, for a different reason (a dependency
conflict, not Psalm-unusability).

## What's built

- `BitPayPaymentService` (implements `PaymentGatewayInterface`):
  `createPayment`/`verifyPayment`/`refund` — refunds always report
  unsupported, since BitPay's `/refunds` endpoint requires the merchant
  facade (ECDSA key-pairing) this POS-facade-only integration doesn't
  implement; a BitPay refund must be issued manually via the merchant
  dashboard, the same documented limitation this app already accepts for
  TrueLayer, for an unrelated reason.
- `BitPayWebhookHandler`: `x-signature` check, then always re-confirms via
  an authenticated `GET /invoices/{id}` before recording a payment — never
  trusts the webhook body's own status field, the same belt-and-braces
  pattern every gateway in this app follows.
- `BitPayPaymentController`: `bitPayInForm`/`bitPayComplete`/`bitPayWebhook`.
- Settings: a single `posToken` password field plus a `sandbox` checkbox (a
  real code branch — `test.bitpay.com` is a genuinely separate host, like
  PayPal/Square).
- A gateway logo sourced from bitpay.com's own apple-touch-icon — BitPay
  isn't in Simple Icons or Wikimedia Commons, checked live.

## Real bugs found and fixed — every one from live evidence, not guessed

**In the client package** (fixed before app-side work started):
`getInvoice()` was hitting a stale wrong path (`api/invoices/{id}` instead
of `invoices/{id}`) that its own test suite hadn't caught, since
`MockHandler` returns its queued response regardless of the request path
actually sent — fixed with a regression test asserting the exact
method+path for both endpoints.

**A project-wide bug, found while fixing BitPay's own**: BitPay's
`createPayment()` failure path set a flash message with BitPay's own error
reason, then returned a bare `getNotFoundResponse()` — which has no body
and never goes through this app's view renderer, so the flash was silently
never shown to anyone. The identical bug turned out to live one layer
deeper, in `PaymentGatewayGuardTrait` — shared by **all 10** gateway
controllers using it (BitPay, TrueLayer, Checkout.com, YooKassa, Square,
Robokassa, Razorpay, Paystack, PayPal, Mercado Pago). Fixed at the trait
level with a new `renderGuardFailure()` (reusing the same `payment_message`
partial every gateway's own `*Complete()` action already renders
successfully) — no changes needed in any of the 10 consuming controllers,
since `resolveConfiguredInvoiceWithBalance()`'s own public signature didn't
change. Found this only because the initial, narrower fix (BitPay's own
`createPayment()` failure specifically) was pushed back on as a shortcut —
what actually matters to a developer setting this gateway up for the first
time is seeing *why* it failed at all, regardless of which gateway or
which guard clause is responsible — so the project-wide root cause was
tracked down and fixed properly instead.

**BitPay's redirect-URL allow-list — undocumented, found via 3 live
rounds**: BitPay's own OpenAPI schema says nothing about pre-registering a
`redirectUrl`. Live testing against a real production account
(yii3i.online) proved that claim wrong: BitPay's Dashboard has a "URL
Redirect Allow List" that rejects a per-invoice path segment
(`"invalid redirectURL: url not whitelisted"`) and doesn't accept wildcard
entries either (a trailing `/*` silently behaved identically to an empty
allow-list). A second attempt — appending `?url_key=...` as a query string
onto an otherwise-correctly-registered fixed URL, on the assumption that
allow-list matching ignores the query string the way OAuth `redirect_uri`
matching usually does — was *also* proven wrong live, against the exact
same registered entry. BitPay's allow-list requires a byte-exact match
with **nothing appended at all, by anyone**. Landed on a fully bare fixed
`redirectUrl` (matching what `notificationUrl` already was), with
`bitPayComplete()` reduced to a generic, non-invoice-specific page — there
is no way to know which invoice a given visit is for, so it says "thank
you, check your invoice for the latest status" rather than anything
specific. The invoice is still only ever actually marked paid by
`BitPayWebhookHandler`, unaffected by any of this.

**The actual account-setup blocker — solved by BitPay's own email, not
by any of the above**: every invoice-creation attempt failed with
`{"error":"Account not setup completely yet."}` regardless of the
redirect-URL work — a red herring that consumed real debugging time before
BitPay's own automated email to the merchant named the real cause
directly: *"Update your Settlement Settings — An invoice could not be
created because you have not yet provided settlement settings for your
BitPay account."* Filling in Settlement Settings (currency, bank country,
payout bank details) cleared it immediately. Nothing in BitPay's own API
error, or in any of the redirect-URL work, pointed at this.

**Two more real webhook bugs, found after a real payment succeeded but
the invoice never flipped to paid**:

1. *Wrong payload envelope.* `BitPayWebhookHandler` was built against a
   flat, no-envelope body — per BitPay's own legacy IPN documentation
   prose, confirmed via WebFetch during the client's original build. The
   *real* payload, confirmed directly against actual deliveries in
   `runtime/logs/app.log`, is
   `{"data": {id, orderId, status, ...}, "event": {name, code, timestamp}}`
   — an envelope, contradicting that earlier doc-based finding.
   `$payload['id']`/`$payload['orderId']` had therefore always resolved to
   empty strings on every real delivery. Fixed to read
   `$payload['data']['id']`/`$payload['data']['orderId']`.
2. *Wrong signature formula.* The client's `verifyWebhookSignature()`
   parsed the body then re-encoded it before hashing, based on how BitPay's
   own reference verifier tool (`bitpay/hmac-tester`) appeared to behave —
   never independently confirmed against a real signed webhook. A
   temporary diagnostic-logging pass (computing four candidate formulas —
   raw/reencoded × base64/hex — against real incoming webhooks, logged
   alongside the actual received `x-signature`) proved it wrong: the
   received signature matched a plain **raw-body** HMAC exactly. Root
   cause: BitPay's real invoice payloads carry large numeric values in
   scientific notation (`"MATIC":1.699895497e+21`, present in every real
   `paymentSubtotals`/`paymentTotals`) that PHP's `json_decode()`/
   `json_encode()` round-trip doesn't reproduce byte-identically — silently
   changing the signed bytes for every real invoice notification. Fixed in
   `rossaddison/bitpay-client` to hash the raw body bytes directly, with no
   parsing step at all; the diagnostic-logging code was removed once its
   job was done.

Deploying the fix needed both a `git pull` **and** `composer install` —
`git pull` alone only updates the app's own `composer.lock`, not the
actual vendored `rossaddison/bitpay-client` package files a `dev-master`
require points at.

## Confirmed live, end to end

A real simulated GBP/BTC payment on a real BitPay sandbox account
(yii3i.online, September 2026): invoice creation → BitPay's hosted
checkout (showing the real business name and amount) → a real payment →
BitPay's own dashboard showing the invoice `COMPLETE` → the webhook
correctly delivered, signature-verified, and parsed → the invoice's own
balance flipped to paid automatically via
`InvPaymentSettlementService::markInvoicePaidAndAdjustStock()`. Every leg
of the integration is now live-verified, not just unit-tested.

## Not done

- Refunds — deliberately unsupported (see above); must be issued manually.
- `sandbox_env_var` in `resources/gateway-status/gateways.json` stays
  `null` — no automated CI ping wired up yet, matching this app's
  established pattern for gateways confirmed working via a human-run live
  test rather than a scripted one.
- A larger, explicitly deferred follow-up surfaced while investigating a
  real SonarCloud duplication finding on this work: all 10 gateway
  controllers using `PaymentGatewayGuardTrait` share near-identical
  constructor/`loadInvoice()`/completion-render boilerplate that could be
  extracted into another shared trait or base class — out of scope for
  this session, since it would touch 9 already-working, live-tested
  integrations beyond BitPay's own.

## Verification

Full-project Psalm clean ("No errors found!") at every step; Testo Unit
suite 1256/1256 (`BitPayPaymentServiceTest`, `BitPayWebhookHandlerTest`,
plus every other gateway's own suite unaffected by the shared-trait
change); `rossaddison/bitpay-client`'s own PHPUnit 15/15, Psalm clean at
errorLevel 1, php-cs-fixer clean. SonarCloud's `new_coverage` gate was
admin-overridden across several of the 8 PRs — a known, understood,
already-documented tooling gap for this codebase (no dedicated controller
tests exist for any of the 10 gateway controllers, not just BitPay's), not
a real quality issue; `new_duplicated_lines_density` was investigated
properly rather than overridden and brought to 0.0% via the
`PaymentGatewayGuardTrait` extraction above.
