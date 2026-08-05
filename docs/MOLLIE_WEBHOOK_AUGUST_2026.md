# Mollie Webhook — August 2026

## Context

An audit of the current `mollie/mollie-api-php` SDK usage in this app (prompted
by "Mollie is next" after a similar audit/fix pass on Robokassa, YooKassa, and
Amazon Pay's tests) found that **Mollie was the only currently-integrated
payment gateway in this app with no webhook at all** — Stripe, Adyen,
GoCardless, Robokassa, and YooKassa all have one as the authoritative source
of "is this actually paid" truth; Mollie payments relied entirely on:

1. The customer's browser successfully completing the redirect back to
   `PaymentInformationController::mollieComplete()`.
2. A reverse lookup there — `$mollie->payments->page()` with no filter, which
   Mollie's API defaults to the **50 most recent payments store-wide**,
   scanned for one whose `metadata.invoice_url_key` matched.

If the customer closed their browser before returning, or 50+ other payments
happened in the interim, that lookup would silently miss and the invoice
would never be marked paid — no fallback caught it later. This is now fixed.

## Ground-truthing Mollie's webhook model

Confirmed the SDK version already pinned in this project
(`mollie/mollie-api-php` `>=3.13.1`, currently resolving to the actual latest
release, v3.13.1/June 2026 — unlike Robokassa/YooKassa, Mollie's SDK is
properly maintained on GitHub itself, no stale-mirror situation here) has
**two separate webhook systems**:

- **"Next-gen webhooks"** (`Mollie\Api\Webhooks\SignatureValidator`,
  `docs/webhooks.md`) — HMAC-SHA256 signed, account-wide subscriptions to
  newer resource types (Payment Links, Payouts, Disputes, Balance events,
  Sales Invoices, …). Registered once via the Mollie dashboard/API, not
  per-payment.
- **"Classic" per-payment webhooks** (`docs/recipes/payments/handle-webhook.md`)
  — a plain form POST carrying only `id`, no signature at all. Set via a
  `webhookUrl` field at payment-creation time (`payments->create()`).
  Authenticity comes from calling back `GET /payments/{id}` with this app's
  own API key and trusting only that response, never the POST body itself —
  the same "re-confirm via an authenticated GET" shape already used for
  Robokassa (OpStateExt) and YooKassa (`GET /payments/{id}`).

This app creates one-off `Payment` resources directly via `payments->create()`
for each invoice — the classic model is what applies, confirmed via that
exact recipe doc, not a guess.

## What changed

- **`MollieWebhookHandler`** (new): reads `id` from the POST body, re-fetches
  the payment via `$mollieClient->payments->get($id)`, and only if
  `isPaid() && !hasRefunds() && !hasChargebacks()` marks the invoice paid —
  matching every other gateway's webhook handler shape in this app
  (idempotency guard: skips if the invoice's balance is already 0, e.g. if
  `mollieComplete()`'s own redirect-time check got there first). Always
  responds 200, per Mollie's own documented rule — even on failure, since
  Mollie retries otherwise and a bad payment id won't be fixed by a retry.
  The `MollieApiClient` is constructor-injected (defaulting to
  `new MollieClient()`) specifically so tests can substitute Mollie's own
  `Mollie\Api\Fake\MockMollieClient` — this is the first Testo test in this
  app to exercise a real SDK's actual JSON hydration/response-parsing code,
  rather than a hand-mocked HTTP response shape.
- **`MolliePaymentController`** (new): a one-method dedicated controller for
  the webhook action — `PaymentInformationController` is already at
  SonarQube's php:S1448 method-count ceiling (20 methods), matching the same
  reasoning already documented there for Adyen/GoCardless/Robokassa/YooKassa.
  Mollie's existing `mollieInForm()`/`mollieComplete()` stay where they are;
  moving already-working code wasn't worth the churn for one new method.
- **`mollieApiClientCreatePayment()`**: now sets `webhookUrl` alongside the
  existing `redirectUrl`. Needs a publicly reachable HTTPS URL to actually
  receive calls from Mollie — a local/dev environment with no public tunnel
  simply won't receive it, the same limitation as any other webhook-based
  gateway in local development.
- **`mollieComplete()`**: gained the idempotency guard the webhook's
  existence now requires — Mollie's own docs note "the webhook may be
  called after the customer has already been redirected" (it can just as
  easily arrive first), so re-running the balance-clearing/recording logic
  when the balance is already 0 would create a duplicate `Merchant` audit
  row via `paymentRecorder->record()`.
- New route `/paymentinformation/mollieWebhook` (CSRF-exempt, unauthenticated
  by design, matching every other gateway webhook route).

## Verification

`php -l` + full-project `vendor/bin/psalm --no-cache` — no errors (isolated
single-file Psalm runs on the new Mockery-heavy test showed spurious
`MixedMethodCall`/`UndefinedInterfaceMethod` findings — a known, previously
documented Psalm-stub-context limitation of scanning test files without the
rest of the project; the full-project run is authoritative and clean). Full
Testo suite — 670/670 passing (first time this session's target file count
included a real SDK fake rather than hand-rolled Guzzle mocks). Full
`vendor/bin/phpunit` — 3,877/3,877 passing. Live-curled the new
`/paymentinformation/mollieWebhook` route directly against the running local
site with an empty POST body, confirming the expected `400 "missing id"`
response rather than a `500` from a DI-wiring mistake.
