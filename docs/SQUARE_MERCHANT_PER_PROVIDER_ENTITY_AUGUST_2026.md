# SquareMerchant — First Per-Provider Payment Entity — August 2026

## Summary

Square payments now write their own `SquareMerchant` audit record (a new
`square_merchant` table) instead of the generic `Merchant` table every
other gateway shares. This is the first of what's meant to become one
entity per payment provider — the concrete case (Square) that motivated
it, not a one-off.

## The problem this solves

`Merchant` is this app's shared, generic payment-audit table: one row per
payment attempt across every gateway (Stripe, Adyen, Braintree, Mollie,
Amazon Pay, GoCardless, Robokassa, YooKassa, Paystack, Razorpay, PayPal,
Square), with a single `provider_reference` string column for whatever
that gateway's own transaction ID is.

That works fine for eleven of the twelve gateways. It doesn't work for
Square, which genuinely needs **two** distinct provider-side identifiers
persisted, serving two different purposes:

- **`order_id`** — what Square's webhook payload actually carries;
  needed to resolve back to this app's own invoice url_key (the invoice's
  url_key is set as the Order's own `reference_id` at Payment Link
  creation time — see `SquarePaymentService::createPayment()`).
- **`payment_id`** — what Square's refund API (`POST /v2/refunds`) is
  actually keyed by.

One `provider_reference` column can hold one of these, not both. Before
this change, `payment_id` was stored (so refunds worked) and
`order_id`→invoice resolution was done via a **live `GET /v2/orders/{id}`
re-fetch on every single webhook call**, never persisted anywhere — see
`SquarePaymentService::getOrderReferenceId()`, which this change doesn't
touch (still a live call; a locally-cached `order_id` doesn't remove the
need to independently resolve *which invoice* that order belongs to).

## What's new

- **`SquareMerchant`** (`src/Infrastructure/Persistence/SquareMerchant/SquareMerchant.php`)
  — a Cycle ORM entity, same shape as `Merchant` minus the `driver` column
  (implicit — the table itself is Square-only) plus `order_id` and
  `payment_id` as separate, independently-settable nullable string
  columns.
- **`SquareMerchantRepository`** / **`SquareMerchantService`**
  (`src/Invoice/SquareMerchant/`) — mirror `MerchantRepository`/
  `MerchantService`'s exact shape and method-naming conventions.
- **`PaymentRecordContext::$secondary_provider_reference`** — a new,
  deliberately generic (not Square-specific) optional field: a second
  provider reference for gateways where one string can't cover both the
  webhook-lookup and refund-capable identifiers. `null` for every other
  gateway.
- **`OnlinePaymentRecorderService`** — `recordAuditRow()` dispatches on
  `$ctx->driver`: `'Square'` writes a `SquareMerchant` row
  (`recordSquareMerchant()`); every other driver keeps writing the shared
  `Merchant` row (`recordGenericMerchant()`), unchanged.
- **`PaymentRefundController`** — `resolveProviderReference()` dispatches
  the same way: Square resolves via `SquareMerchantRepository`
  (`->getPaymentId()`, the refund-capable one), every other gateway keeps
  resolving via `MerchantRepository`, unchanged.

## Replace, not supplement

Square payments stop writing to the generic `merchant` table entirely —
this isn't an additive side table joined by `merchant_id`. Deliberate
choice, made explicit before writing any code: a real per-provider entity
replacing the generic one for Square specifically, with the *dispatch*
happening in the two places that ever needed to know which table to use
(`OnlinePaymentRecorderService`, `PaymentRefundController`), both already
using a `match`/dispatch-per-driver idiom elsewhere in this app (see
`PaymentRefundController::dispatchRefund()`), so this isn't new
architectural surface — it's the same pattern extended by one more
branch.

## Scope: Square only, for now

The other eleven gateways don't have Square's specific dual-reference
problem — confirmed by checking Razorpay's and PayPal's own webhook
handlers directly: both resolve their own two-ID wrinkles
(payment-link-id-vs-payment-id, order-vs-capture) entirely from within a
single webhook call, with no need to persist a second reference
afterward. Building out `StripeMerchant`, `AdyenMerchant`, and so on isn't
blocked on anything technical — it's simply not needed yet, and doing all
twelve in one sitting (new entities, new tables, a real data migration for
every gateway's existing historical rows) was explicitly scoped out in
favor of getting the one concrete, motivating case right first. Future
gateways needing their own entity follow this exact template.

## Verification

- `DESCRIBE square_merchant` confirms the table (`id`, `inv_id`, `date`,
  `successful`, `response`, `reference`, `order_id`, `payment_id`) was
  created cleanly via `BUILD_DATABASE=true` schema sync.
- Full-project Psalm (`vendor/bin/psalm --no-cache`): no errors found.
- Full Testo suite: 768/768 passing (12 new tests —
  `SquareMerchantTest`, `SquareMerchantServiceTest`).
- Full PHPUnit suite: 3,877/3,877 passing (23 pre-existing notices only;
  `PaymentRefundControllerTest` updated for the new
  `SquareMerchantRepository` constructor argument, matching this
  session's established pattern for every prior gateway addition).
- Live-curled `squareInForm`, `payment/refund/{id}/square`, and
  `mollieInForm` (to confirm `OnlinePaymentRecorderService`'s new
  constructor dependency resolves cleanly from an unrelated gateway's own
  controller too) — all clean `404`s, no DI resolution errors anywhere in
  the new dependency chain.

**⚠️ Not verified against a real Square account** — same standing
limitation as the rest of Square's integration (the user has no
registered company to test against). The write-path logic itself is
unit-tested; what isn't verified is a real webhook actually reaching it
end-to-end.
