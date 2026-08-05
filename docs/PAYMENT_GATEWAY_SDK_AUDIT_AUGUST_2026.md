# Payment Gateway SDK Audit — August 2026

## Context

Continuing the sweep started with Robokassa/YooKassa/Amazon Pay's tests/
Mollie's missing webhook (see docs/MOLLIE_WEBHOOK_AUGUST_2026.md): audited
the remaining gateways' SDK usage for version drift, deprecated methods, and
the same class of "missing async confirmation" gap Mollie had.

## Findings

**Stripe** (`stripe/stripe-php` `>=19`, resolving to v21.1.1, current) —
clean. Real signature verification via `Stripe\Webhook::constructEvent()`,
idempotency guard already in `StripeWebhookHandler::resolveContext()`, no
deprecated methods in use.

**Adyen** (`adyen/php-api-library` `^30.0.2`, current — released the same
day as this audit) — clean. Real HMAC verification via
`Adyen\Util\HmacSignature`, no deprecated calls. Already extensively
live-tested in this project's history (see
docs/ADYEN_GATEWAY_LIVE_TESTING_AND_CSP_FIXES_JULY_2026.md).

**Braintree** (`braintree/braintree_php`) — **real finding**: pinned at
6.36.0, one minor version behind the actual latest (6.37.0), whose
changelog documents a **security fix**: "Fix path traversal vulnerability in
`Dispute` and `Address` gateways by validating that IDs used in request
paths do not contain path separators or relative-path segments." This app
doesn't call either gateway (confirmed via a full-codebase grep for
`Dispute`/`->address(`), so it wasn't directly exploitable here — upgraded
anyway since `composer.json`'s constraint was already open-ended
(`>=6.36.0`) and the bump is a same-minor-version patch, effectively free.
No webhook gap here unlike Mollie: Braintree's Drop-in UI +
`gateway->transaction()->sale()` is a synchronous flow — the payment
outcome is known within the same request/response cycle that processes the
customer's submission, so there's no structural need for an async
webhook the way there was for Mollie's redirect-based flow.

**GoCardless** (`gocardless/gocardless-pro` `^8.0`, resolving to v8.0.3,
current) — clean. Real `Webhook-Signature` verification via
`GoCardlessPro\Webhook::isSignatureValid()`, already has its own webhook
handler (built earlier this session alongside the customer-facing checkout
flow work).

**Amazon Pay** (`amzn/amazon-pay-api-sdk-php` `^2.7.2`, current) — **real
finding**: two `@psalm-suppress MixedReturnStatement` annotations on
`generateButtonSignature()`'s call site and body, which directly violates
this project's own established convention against `@psalm-suppress` (find a
structural fix instead). Root cause: the SDK's own
`Client::generateButtonSignature($payload)` declares no return type at all
(untyped legacy code) — it always returns a string on success, throwing
`\Exception` itself if RSA signing fails. Fixed with an explicit `(string)`
cast at the one place the untyped SDK call actually happens, removing both
suppressions — a real type narrowing, not a guess, matching the
`(string) $this->settings->decode(...)` cast pattern already used
throughout this codebase for the same reason.

**Open Banking** (`rossaddison/openbanking-client`, `dev-main`) — this
project's own maintained package, not a third-party dependency subject to
the same version-drift risk; already extensively verified in this
project's history (see project memory). No further audit performed here.

## Verification

Full-project `vendor/bin/psalm --no-cache` — no errors. Full Testo suite —
670/670 passing. Full `vendor/bin/phpunit` — 3,877/3,877 passing.
