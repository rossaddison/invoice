# Payment Gateway Refund — Live Testing & Adyen v6 Upgrade

## Why

The refund feature added earlier (`PaymentRefundController`, the refund dropdown on
`payment/index`) had only ever been proven via a throwaway script calling each
gateway's `refund()` service method directly — never through the actual controller
route, permission check, or UI. Stripe and Braintree had a full live pass; Adyen and
Mollie had only reached a clean provider-side *rejection* against a fake reference,
proving credentials worked but not a real success path. This pass drove a real
invoice through each of the four PCI-compliant gateways end-to-end — pay, then
refund via the real `payment/index` dropdown — and verified the result against each
provider's own API/dashboard, not just this app's own database.

## Stripe — full pass

Paid via the real webhook flow (`stripe listen --forward-to`, card
`4242 4242 4242 4242`), then refunded via the UI. Verified three ways: Stripe's API
(`ch_3TvOGkB4qrp1Ddy40BvOvrwa` → `refunded: true`, `amount_refunded: 14600`, an exact
full refund), the `refund.created`/`charge.refunded` webhook events forwarding
cleanly (`200`, no handler errors), and the `Payment` record's note correctly
capturing the real refund ID (`re_3TvOGkB4qrp1Ddy40U2BoaiU`).

## Braintree — full pass, sandbox settlement gotcha

Braintree sandbox transactions sit in `submitted_for_settlement` and reject refund
until settled — production settles overnight automatically, sandbox never does. Forced
it with `Gateway::testing()->settle($id)` on the app's own configured `Gateway`
instance (calling the SDK's global `Braintree\Test\Transaction::settle()` instead is a
trap: it hits the unconfigured global `Configuration::gateway()` and fails trying to
connect to `localhost:443`). Refund transaction `3q0kyzh3` confirmed via the API:
`type=credit`, `amount=125.00`, exact full refund; `Payment` note recorded correctly.

## Mollie — full pass, first genuine success-path test

Redirect-based flow, no webhook. This closed a real gap: Mollie's `refund()` had
previously only been wiring-tested against a fake reference. Refund
`re_bLkKwhA2WUppxdjKqkFUJ` confirmed via the API: `amount=125.00`, exact full refund.
Status was `pending` (not yet `refunded`) at verification time — normal async
settlement lag in Mollie's sandbox, matching the same pattern seen on the original
payment testing pass, not an error.

## Adyen — a real production bug found and fixed

Attempting to even load the Adyen Drop-in widget failed outright with a generic
"An unknown error occurred" card. The real cause, only visible in the browser
console: **`Uncaught Error: The following properties should not be passed to the
client: askDonation`**. Adyen's `/sessions` response now always includes a
Giving/Donation-related `askDonation` field; the pinned Adyen Web SDK **v5.40.0**
rejects any session field it doesn't recognise, and support for `askDonation` was
only added in Web SDK **v6.36.0**.

Fixed by upgrading to v6.41.0, which required real code changes, not just a version
bump:

- **`AdyenAsset.php`**: SDK version bumped to `6.41.0`.
- **`payment-adyen.ts`**: rewritten against the *actual* v6 CDN bundle (downloaded
  and inspected directly, not just the docs) — the global renamed
  `window.AdyenCheckout` → `window.AdyenWeb`, and Drop-in creation moved from
  `checkout.create('dropin')` to `new AdyenWeb.Dropin(checkout).mount(...)`. Uses
  `globalThis`, per this project's convention.
- **`AdyenPaymentController.php`**: v6 makes `countryCode` a *mandatory*
  `AdyenCheckout()` config field. Resolved from the invoice's client's stored
  country name via the existing `CountryHelper::getCountryIdentificationCodeWithLeague()`
  (league/iso3166), already used elsewhere for Peppol — falls back to `GB` if
  unresolved, rather than sending Adyen an empty value.
- Confirmed no PHP-side SDK bump was needed: `adyen/php-api-library` v30.0.1 already
  targets Checkout API v71, well above v6's v69 minimum.

Two more traps hit along the way, both environment/process issues rather than code
bugs in the final state:

- **Stale bundle**: `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` is only
  rebuilt by the pre-commit hook. After editing `payment-adyen.ts` but before
  committing, the browser kept loading the old pre-v6 bundle
  (`Uncaught ReferenceError: AdyenCheckout is not defined`) — fixed by running
  `npm run build:typescript:prod` manually mid-session.
- **Wrong test card CVC**: `4111 1111 1111 1111` with an arbitrary CVC produced a
  genuine `Refused` in Adyen's sandbox. Unlike Stripe/Braintree/Mollie, Adyen's test
  cards need the *exact* documented combination to guarantee approval:
  `4111 1111 1111 1111`, expiry `03/2030`, CVC `737`.

### Verifying payment and refund without a public webhook endpoint

`AdyenPaymentController::adyenComplete()` is deliberately read-only — the invoice is
marked paid *exclusively* by `AdyenWebhookHandler`, driven by a real HMAC-signed
`AUTHORISATION` notification from Adyen's servers. Unlike Stripe there's no CLI tool
to forward that to `localhost`, and this WAMP install isn't publicly reachable.

Worked around locally, with no tunnel or public exposure: after a real sandbox
payment, the transaction's genuine `pspReference` was pulled from Adyen's Customer
Area (`ca-test.adyen.com`), then a throwaway console command built the same
`NotificationRequestItem` payload the real webhook would send — real pspReference,
real merchant reference (the invoice's `url_key`), real amount — signed it with the
app's own configured HMAC key via `Adyen\Util\HmacSignature::calculateNotificationHMAC()`
(the exact function `AdyenWebhookHandler` validates against), and POSTed it to the
local `/paymentinformation/adyenWebhook` route. Accepted (`200 [accepted]`), and the
invoice/`Merchant`/`Payment` records updated correctly — this exercises the real
signature-verification and handler code end-to-end against genuine transaction data,
the same conceptual role `stripe listen --forward-to` plays for Stripe.

The refund itself (`SNQ73X46M5992P65`) was confirmed three ways: the API's own
`received` status, the `Payment` note, and Adyen's dashboard — which greys out
"Refund payment" for an already-refunding transaction specifically *because* a
refund is in progress, itself a positive confirmation once every other disable-reason
in Adyen's own tooltip (role, capture status, dispute, method restriction) is ruled
out.

## Summary

| Gateway | Refund result | Notable finding |
|---|---|---|
| Stripe | Full pass | — |
| Braintree | Full pass | Sandbox needs manual force-settle before refund is possible |
| Mollie | Full pass | First real success-path test (previously fake-reference only) |
| Adyen | Full pass | Real bug: Web SDK v5 crashed on Adyen's own `askDonation` session field; fixed via v6 upgrade |

Amazon Pay refund remains untestable via the UI — a pre-existing, separate gap where
Amazon Pay payments never get written as `Payment`/`Merchant` rows at all, out of
scope for this pass.
