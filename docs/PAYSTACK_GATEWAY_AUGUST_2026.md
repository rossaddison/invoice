# Paystack Gateway — August 2026

## Summary

Added **Paystack** as this app's first Africa-region payment gateway, following the
exact architectural pattern established this session for Robokassa/YooKassa: a
direct HTTP integration against Paystack's own REST API (no third-party SDK
dependency), a dedicated `PaystackPaymentController` (kept out of
`PaymentInformationController`, which is already at SonarQube's php:S1448
method-count ceiling), a signed webhook handler, and full Testo test coverage.

## Ground-truthing

Paystack's own primary documentation site (`paystack.com/docs`) returned HTTP 403
to every fetch attempt this session (bot-protection) and could not be used at all.
Paystack's own GitHub org, `PaystackHQ/paystack-php`, turned out to just be a stale
2017 mirror of the community package `yabacon/paystack-php` (same author, same
package name) — not a genuinely first-party, actively-maintained SDK. That
community package (more recently touched, 2023, 116 stars, still a small
third-party package) was read directly from its GitHub source **for research
purposes only** — never installed as a composer dependency — per this project's
established caution around small third-party packages.

Confirmed directly from that SDK's real executable source:

- Base URL: `https://api.paystack.co` (`Helpers/Router::PAYSTACK_API_ROOT`).
- Auth: `Authorization: Bearer {secretKey}` (secret key starts with `sk_`).
- Response envelope: `{status: bool, message: string, data: {...}}`.
- Endpoints: `POST /transaction/initialize`, `GET /transaction/verify/{reference}`.
- Webhook signature: `X-Paystack-Signature` = `hash_hmac('sha512', $rawBody, $secretKey)`
  — signed with the merchant's own API **secret key**, not a separate
  webhook-specific secret (`Event::validFor()`, read verbatim). The reference
  implementation compares with a plain `===`; this app's `PaystackSignatureService`
  uses `hash_equals()` instead, matching every other signature check in this app.

**Not independently confirmed against primary docs**: the `/refund` endpoint. The
SDK read above has no refund route at all (its static `$ROUTES` array covers
`customer, page, plan, subscription, transaction, subaccount, balance, bank,
decision, integration, settlement, transfer, transferrecipient, invoice` — no
`refund`). `PaystackPaymentService::refund()` is built from Paystack's
well-established public API shape (`POST /refund` with `transaction`/`amount`
body fields), not a primary-source confirmation — flagged explicitly in its own
docblock.

## Architecture

Mirrors Robokassa/YooKassa exactly:

- `PaystackSignatureService` — pure webhook-signature verification, no I/O.
- `PaystackPaymentService` — implements `PaymentGatewayInterface`
  (`getDriverKey()`, `isConfigured()`, `createPayment()`, `verifyPayment()`,
  `refund()`), direct Guzzle HTTP calls, no SDK.
- `PaystackWebhookHandler` — verifies the `X-Paystack-Signature` header, then
  **always** re-confirms via an authenticated `GET /transaction/verify/{reference}`
  before marking an invoice paid — never trusts the webhook body's own
  `data.status` alone, the same belt-and-braces pattern already used for every
  HMAC-signed gateway in this app.
- `PaystackPaymentController` — thin `paystackInForm()` (302 straight to
  Paystack's hosted `authorization_url`) / `paystackComplete()` (read-only,
  re-reads current balance) / `paystackWebhook()` trio, matching
  `RobokassaPaymentController`/`YookassaPaymentController`'s shape.
- Wired into `PaymentInformationController::pciCompliantGatewayInForms()`'s
  dispatch `match`, `PaymentRefundController`'s constructor + `dispatchRefund()`
  match, `routes-payment-information.php`, and
  `CsrfExemptMiddleware::EXEMPT_PATH_SUBSTRINGS` (the webhook route can't carry
  a CSRF token).
- Settings: a single `secretKey` field plus an informational `sandbox` checkbox
  — like Mollie/YooKassa, Paystack's test vs live mode is purely which secret
  key prefix (`sk_test_`/`sk_live_`) is configured against the same base URL,
  not a separate code branch.

Paystack **requires** a customer email address to initialize a transaction
(unlike most other gateways in this app, there's no way to omit it) —
`paystackInForm()` reads it from `$invoice->getClient()?->getClientEmail()` and
shows a clear warning message if the client has none on file, rather than
sending an empty string Paystack would reject.

## Regions

`resources/gateway-status/gateways.json` records Paystack's core supported
markets — Nigeria, Ghana, South Africa, Kenya, Côte d'Ivoire, Egypt, and Rwanda —
based on general public knowledge of Paystack's own market coverage (not
independently re-verified against a primary source this session, since
`paystack.com` itself was unreachable). This fills a real gap Stripe's own
Africa support doesn't cover directly (Stripe's Africa reach is "extended
network only", via Paystack itself).

## ⚠️ Untested against a real account

Per the user's own statement, they have no registered company and cannot create
even a test Paystack account — the same practical barrier already hit with
YooKassa. `sandbox_status` on the `/gateway-status` page stays `untested`
permanently for this reason. This integration is verified only against Testo's
mocked HTTP responses and the SDK's ground-truthed source, never against a real
Paystack account. Anyone enabling this gateway in production should treat it as
unverified end-to-end until a real Paystack merchant account confirms it.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite: all tests passing, including 21 new Paystack tests
  (`PaystackSignatureServiceTest`, `PaystackPaymentServiceTest`).
- Full PHPUnit suite (3,877 tests): all passing, after updating the legacy
  `PaymentRefundControllerTest` to pass the new `PaystackPaymentService`
  constructor argument.
- Live-curled the new routes against the running local site
  (`http://invoice.myhost`): `paystackWebhook` returns a clean `400` for an
  unsigned request (not a `500` from a DI-wiring mistake); `paystackInForm`
  returns a clean `404` for a nonexistent invoice url_key.
