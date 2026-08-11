# Gateway Title Links — Where Credentials Actually Come From

## Summary

Each payment gateway's section title in Settings → Online Payment now
links to two places, side by side:

1. **A permalink to that exact section** (`#gateway-settings-{driver}`,
   built from `$urlFastRouteGenerator->generate('setting/tabIndex')`) —
   deep-links straight past the tab-switching JS, useful for support docs
   or conversations ("Settings → Online Payment → Stripe") instead of
   "scroll down to find it."
2. **"Get credentials →"**, an external link straight to that provider's
   own developer/API dashboard — the actual page where the Access
   Token/API Key/Secret you're about to paste into this app's fields
   comes from.

This exists because finding the right page on each provider's own site
was a recurring, real time cost this session — Square's own onboarding
flow alone drew "what a bloody rigmarole!!!!!" from the user navigating
it live. One click from the settings page removes that hunt entirely.

## Why these are hand-verified, not looked up

A wrong link is worse than no link — it sends someone confidently to the
wrong page instead of making them search, which they'd have done anyway.
So `SettingPaymentTrait::gatewayCredentialUrls()` is filled in **one
gateway at a time**, only once the user has actually confirmed the URL
against their own real account (not general public knowledge, not a
guess, not something scraped from documentation that may be stale). A
gateway with no entry yet simply shows no link — the title itself still
works as a permalink either way.

## Scope: one link per gateway, not per field

Some gateways need credentials from more than one page (Square needed
three: Credentials, Locations, Webhooks — see
`reference_square_sandbox_credentials_setup_steps` in project memory).
This link is deliberately the provider's **main dashboard entry point**
for that gateway, not one link per individual field — keeps the UI
simple and the URL list maintainable; from the main dashboard the
specific sub-page is usually one or two clicks away.

## URLs confirmed so far (August 2026)

| Gateway | URL | Note |
|---|---|---|
| Adyen | `https://ca-test.adyen.com/ca/ui/developers/api-credentials/` | Real sandbox account confirmed (`RossAddisonServicesECOM`) — live-tested end-to-end, see `docs/ADYEN_WEBHOOK_HMAC_KEY_NOT_SAVED_AUGUST_2026.md`. |
| GoCardless | `https://manage-sandbox.gocardless.com/sign-in?redirect=%2Fdevelopers` | Sandbox login, redirects straight to Developers. |
| Mollie | `https://my.mollie.com/dashboard/login?lang=en` | |
| PayPal | `https://developer.paypal.com/dashboard/applications/sandbox` | Lands on the sandbox Apps & Credentials list — Client ID/Secret and the webhook subscription's own config are one or two clicks further in. Real sandbox account confirmed — live-tested end-to-end, see `docs/PAYPAL_GATEWAY_AUGUST_2026.md`. |
| Square | `https://app.squareup.com/dashboard/apps/my-applications` | Lands on the Applications list — create an application (if none yet), then click "Manage" to reach Credentials/Locations/Webhooks. |
| Stripe | `https://dashboard.stripe.com` | |

These six are exactly the gateways confirmed live-tested against a real
account (see `project_square_integration_complete` and related memory,
plus this same session's PayPal/Adyen live-testing) — chosen as each
batch specifically because their URLs could be verified rather than
guessed. The remaining gateways (Amazon Pay, Braintree, Open Banking
Tink/Wonderful, Paystack, Razorpay, Robokassa, StoreCove, Yookassa) are
unfilled until each is confirmed the same way.

## Implementation

- `SettingPaymentTrait::gatewayCredentialUrls(): array` — the lookup
  table itself, keyed by the same lowercased driver name
  `activePaymentGateways()` uses.
- `SettingController::tabIndex()` — gained a `FastRouteGenerator
  $urlFastRouteGenerator` parameter (DI-resolved, same pattern already
  used in `debugIndex()`), and passes both that and
  `gatewayCredentialUrls()`'s result into the `online_payment` partial's
  render parameters.
- `partial_settings_online_payment.php` — each gateway's title is now
  wrapped in a same-page anchor link; the external "Get credentials →"
  link renders immediately after it only when
  `$gateway_credential_urls[$d]` is set and non-empty.
- `resources/messages/en/app.php` — new `online.payment.get.credentials`
  key.
