# Stripe Pay by Bank — Open Banking for UK & Finland Customers

## What this is

**Pay by Bank** is a Stripe payment method that lets a customer pay an
invoice directly from their bank account instead of a card, using
[Open Banking](https://www.openbanking.org.uk/) rails. Instead of typing
card details, the customer picks their bank at checkout and is redirected
into their own bank's mobile app or web banking portal to approve the
payment — Stripe never sees the customer's banking credentials.

Reference: <https://docs.stripe.com/payments/pay-by-bank>

## Where it's supported (per Stripe's docs, checked July 2026)

| | |
|---|---|
| Customer locations | Finland, France\*, Germany\*, Ireland\*, **United Kingdom** |
| Currencies | GBP, EUR |
| Payment range | £0.50 – £10,000 (higher limits available on request from Stripe) |

\* France, Germany and Ireland are in private preview at the time of
writing — UK and Finland are generally available, which is why this doc
calls those two out specifically.

Merchant-side, 35+ countries (including the UK and Finland) can *accept*
Pay by Bank payments — the country restriction above is about where the
*paying customer's* bank account has to be.

## Why "Pay by Bank" instead of cards

Pay by Bank settles account-to-account, so it carries none of the
card-network interchange fees, has no chargebacks (the customer already
authenticated with their own bank), and refunds are supported for up to
730 days. The trade-off: no recurring payments, no manual capture, and no
Checkout subscriptions — it's a single-use, one-off payment method, which
suits how this app uses Stripe (paying a single invoice's balance).

## How this app already supports it — no code change needed

`StripePaymentService::createPaymentIntent()` already creates every
PaymentIntent with `automatic_payment_methods` enabled rather than a
hardcoded `payment_method_types` list:

```php
// src/Invoice/PaymentInformation/Service/StripePaymentService.php
'automatic_payment_methods' => [
    'enabled' => true,
],
```

Stripe reads this flag and decides which payment methods to actually
present at checkout based entirely on **what's turned on in the Stripe
Dashboard for that account** — not on anything in this codebase. That
means switching the app over to "Pay by Bank" enabled only is a Stripe Dashboard
configuration change, not a deployment.

## Enabling "Pay by Bank" in the Stripe Dashboard only

1. Log in to the [Stripe Dashboard](https://dashboard.stripe.com/settings/payment_methods)
   with the account whose secret/publishable keys are configured under
   **Settings → Online Payments → Stripe** in this app.
2. Go to **Settings → Payment methods**.
3. Turn **on** *Pay by bank*.
4. Turn **off** every other method you don't want offered (Cards, Google
   Pay, Apple Pay, etc.) if the goal is Pay by Bank exclusively.
5. Save. No key rotation, redeploy, or code change is required — the next
   PaymentIntent this app creates via `createPaymentIntent()` will surface
   only the methods enabled here.

## The customer's payment flow

1. Customer opens the invoice's Stripe payment page in this app and clicks
   pay; **Pay by bank** is now the only option shown (per the Dashboard
   setting above).
2. Customer selects their bank from Stripe's bank list — UK banks (Barclays,
   HSBC, Lloyds, NatWest, Monzo, etc.) and Finnish banks are both driven by
   the same Open Banking flow.
3. Customer is redirected into **their own bank's app or web banking**, logs
   in with their normal bank credentials, and reviews/approves the payment
   there (SMS/app-based strong customer authentication, entirely on the
   bank's side).
4. Customer is redirected back to this app; Stripe sends a
   `payment_intent.succeeded` (or `payment_intent.payment_failed`) webhook,
   which `StripeWebhookHandler` — see
   [Stripe Payment Gateway — Webhook](STRIPE_PAYMENT_GATEWAY_WEBHOOK.md) —
   already handles as the sole writer of payment status. No new webhook
   event types need to be added for Pay by Bank; it uses the same
   PaymentIntent lifecycle as card payments.

## Limitations to be aware of

- **No recurring payments / no Checkout subscriptions** — fine for this
  app's one-off invoice-balance payment flow.
- **No manual capture** — payments are captured immediately on approval.
- **No disputes/chargebacks** — the trade-off for the customer having
  already authenticated with their own bank.
- **Refunds are supported** (up to 730 days after payment) and flow through
  this app's existing Stripe refund path unchanged, since refunds operate
  on the PaymentIntent/Charge, not the payment method type.

## Note: This is unrelated to rossaddison/openbanking-client

This is Stripe's own native Open Banking payment method, configured entirely
in the Stripe Dashboard and processed through the existing
`StripePaymentService`/Stripe webhook path. It is a separate integration from
the standalone [`rossaddison/openbanking-client`](https://github.com/rossaddison/openbanking-client)
package (Wonderful/Tink-based Open Banking providers, extracted from this app
as its own package) — the two are not related and do not share any code,
settings, or credentials.

## Testing locally

Use the same Stripe CLI setup documented in
[Stripe Payment Gateway — Webhook](STRIPE_PAYMENT_GATEWAY_WEBHOOK.md#local-testing-setup-stripe-cli).
Stripe's test mode provides simulated bank redirect pages for Pay by Bank,
so `stripe listen --forward-to http://localhost/en/paymentinformation/stripeWebhook`
plus a real test-mode payment through the UI is enough to exercise the full
flow without a real UK or Finnish bank account.
