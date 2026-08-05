# Razorpay Gateway — August 2026

## Summary

Added **Razorpay** as this app's first India-region payment gateway, built
against its **Payment Links API** — a hosted checkout page matching this
app's existing redirect-based pattern (Robokassa/YooKassa/Paystack/Mollie/
GoCardless), rather than Razorpay's more commonly-integrated Orders +
embedded-JS-Checkout-widget flow, which would require client-side JS
integration this app's architecture doesn't otherwise need.

## Ground-truthing

Razorpay's official PHP SDK, `razorpay/razorpay` (`github.com/razorpay/
razorpay-php`), is genuinely first-party and actively maintained (pushed
2026-07-23, not archived, 207 stars) — unlike Robokassa/Paystack's thin,
unofficial community packages. It was deliberately **not installed** as a
composer dependency, though: its HTTP layer is built on `rmccue/requests`, a
different HTTP client entirely from the Guzzle client every other gateway in
this app is built and tested against (via `GuzzleHttp\Handler\MockHandler`),
and it ships no fake/mock client equivalent to Mollie's
`Mollie\Api\Fake\MockMollieClient` — its own test suite is live integration
tests requiring real `RAZORPAY_API_KEY`/`RAZORPAY_API_SECRET` credentials
(confirmed via `tests/TestCase.php`). Rather than add a second HTTP-client
dependency for one gateway, this integration is built as direct Guzzle HTTP
calls instead, with every URL/field/formula still ground-truthed directly
against that SDK's real executable source.

Confirmed directly from the SDK source (`Api.php`, `Request.php`,
`Entity.php`, `PaymentLink.php`, `Refund.php`, `Utility.php`):

- Base URL `https://api.razorpay.com`.
- Auth: HTTP Basic, `key_id:key_secret` — **not** a Bearer token, unlike
  Paystack/Stripe.
- `Entity::getEntityUrl()` confirms `POST /v1/payment_links`,
  `GET /v1/payment_links/{id}`, and (via `Payment::refund()` → `Refund`
  entity) `POST /v1/refunds` with `payment_id` in the body.
- Payment Link callback signature: `hash_hmac('sha256',
  "{payment_link_id}|{reference_id}|{status}|{payment_id}", $keySecret)`.
- Webhook signature: the exact same HMAC-SHA256 formula, but over the raw
  request body and with a **separate webhook secret** (configured in the
  Razorpay Dashboard), not the API key secret.

Unlike Paystack (whose docs site 403'd on every fetch attempt), Razorpay's
own docs site was reachable this session and confirmed:

- The create-response fields `id`, `short_url` (the hosted checkout URL),
  and initial `status: "created"`
  (`razorpay.com/docs/api/payments/payment-links/create-standard/`).
- The `payment_link.paid` webhook event name and its
  `payload.payment_link.entity`/`payload.payment.entity` nesting
  (`razorpay.com/docs/webhooks/payment-links/`).

**Not independently confirmed**: the exact `X-Razorpay-Signature` header name
— its dedicated signature-validation doc page 404'd on fetch. Based on
well-established, consistently-documented general public knowledge instead,
flagged explicitly in `RazorpaySignatureService`'s own docblock.

## Architecture

Mirrors Paystack/Robokassa/YooKassa, with one structural wrinkle unique to
Razorpay: refunds are per-**payment**, not per-payment-**link** — the object
this app creates and redirects the customer to (a Payment Link) is not the
same object refunds operate against (the resulting Payment). To handle this:

- `RazorpayPaymentService::createPayment()` promotes
  `$metadata['invoiceUrlKey']` to Razorpay's first-class `reference_id`
  field (rather than only nesting it inside `notes`) — this is what both the
  webhook payload and the callback signature payload echo back verbatim.
- `RazorpayWebhookHandler` extracts **both** the payment_link id (used to
  re-confirm via `GET /v1/payment_links/{id}`) and the underlying payment id
  from the same trusted (signature-verified) webhook payload, storing the
  **payment id** as the Merchant audit record's `provider_reference` —
  exactly what `RazorpayPaymentService::refund()` needs later.
- `razorpayComplete()` stays strictly read-only, the same defense-in-depth
  convention every other gateway's `Complete()` action follows in this app —
  even though Razorpay's Payment Link callback query string is genuinely
  self-verifying via its own `razorpay_signature` HMAC, this app never trusts
  a client redirect alone to mark an invoice paid.

Settings: `keyId` + `keySecret` (genuinely different per test/live mode,
`rzp_test_`/`rzp_live_`, same base URL) plus a separate `webhookSecret` (same
pattern as Stripe/Adyen/GoCardless's own webhook secret field).

## Regions

Asia (India specifically) — `resources/gateway-status/gateways.json`.

## ⚠️ Untested against a real account

Per the user's own statement, they have no registered company and cannot
create even a test Razorpay account — the same practical barrier already hit
with YooKassa and Paystack. `sandbox_status` on the `/gateway-status` page
stays `untested` permanently for this reason. This integration is verified
only against Testo's mocked HTTP responses and the SDK's ground-truthed
source, never against a real Razorpay account. Anyone enabling this gateway
in production should treat it as unverified end-to-end until a real Razorpay
merchant account confirms it.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite: all tests passing, including 20 new Razorpay tests
  (`RazorpaySignatureServiceTest`, `RazorpayPaymentServiceTest`).
- Full PHPUnit suite (3,877 tests): all passing, after updating the legacy
  `PaymentRefundControllerTest` to pass the new `RazorpayPaymentService`
  constructor argument.
- Live-curled the new routes against the running local site
  (`http://invoice.myhost`): `razorpayWebhook` returns a clean `400` for an
  unsigned request; `razorpayInForm` returns a clean `404` for a nonexistent
  invoice url_key.
