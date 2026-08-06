# Square Gateway — August 2026

## Summary

Added **Square** as a new payment gateway, built against Square's **Checkout
API Payment Links** (`POST /v2/online-checkout/payment-links`, an
Order-based hosted checkout page), matching the redirect-based pattern
already established for Robokassa/YooKassa/Paystack/Razorpay/PayPal — not
Square's Web Payments SDK, which would need client-side card-nonce JS
integration.

## Ground-truthing

Square's official PHP SDK, `square/square-php-sdk`
(`github.com/square/square-php-sdk`), is genuinely first-party and actively
maintained (pushed 2026-07-14, not archived). It was deliberately **not
installed** as a composer dependency — same reasoning as Razorpay and
PayPal: its HTTP layer is APIMatic-generated code built on
`apimatic/unirest-php`, a different HTTP client from the Guzzle client every
other gateway in this app is built and tested against, with no mockable
test double. It was read directly from GitHub for research purposes only.

Confirmed directly from the SDK source (`Environments.php`,
`SquareClient.php`, `Checkout/PaymentLinks/PaymentLinksClient.php`,
`Orders/OrdersClient.php`, `Payments/PaymentsClient.php`,
`Refunds/RefundsClient.php`, `Types/Order.php`, `Types/OrderLineItem.php`,
`Refunds/Requests/RefundPaymentRequest.php`):

- Base URLs `https://connect.squareup.com` (production) /
  `https://connect.squareupsandbox.com` (sandbox) — like PayPal, Square's
  sandbox setting really is a different base URL, not just a different
  credential against the same one.
- Auth: `Authorization: Bearer {accessToken}` plus a required
  `Square-Version: {date}` header (Square's whole-API date-based versioning
  scheme).
- `POST /v2/online-checkout/payment-links`, `GET /v2/payments/{id}`,
  `GET /v2/orders/{id}`, `POST /v2/refunds`.
- `Order.reference_id` (not available via the simpler "Quick Pay" ad hoc
  item shape — the fuller Order-based request is used specifically so this
  field can be set) is the only place to carry this app's own invoice
  url_key through Square's flow.
- `OrderLineItem.quantity` is a **string** field, confirmed from the SDK's
  own type declaration — not an integer.

Confirmed directly against Square's own current developer docs (reachable
this session, unlike Paystack's 403s):

- The webhook signature formula: `x-square-hmacsha256-signature` =
  `base64_encode(hash_hmac('sha256', $notificationUrl . $rawBody,
  $signatureKey, true))` — notably hashing the **notification URL
  concatenated with the raw body**, unlike every other HMAC-signed gateway
  in this app, and base64- rather than hex-encoded.
- The `payment.created`/`payment.updated` webhook event names.
- The payload's `data.object.payment.{id, status, order_id}` shape, with
  `status: "COMPLETED"` on success.

## Architecture

Mirrors Paystack/Razorpay/PayPal, with one genuine architectural wrinkle:
the Payment webhook payload carries **no reference back to this app's own
invoice url_key**, only `order_id`. `SquarePaymentService::createPayment()`
sets the invoice's url_key as the underlying Order's own `reference_id` at
Payment Link creation time; `SquareWebhookHandler` makes a **second** GET
call (`getOrderReferenceId()`, `GET /v2/orders/{id}`) to resolve that
`reference_id` back from the `order_id` the webhook payload actually
carries — a similar shape to Razorpay's payment-link-id-vs-payment-id
wrinkle, but resolved via an extra API call rather than a stored dual
reference.

The webhook's signature check reconstructs the "notification URL" from the
inbound request's own URI (`$request->getUri()`), which must match exactly
what's configured for this webhook subscription in the Square Developer
Dashboard — flagged explicitly in `SquareWebhookHandler`'s own docblock as
something that would need revisiting if this app is ever deployed behind a
reverse proxy that changes the scheme/host presented to PHP (a degradation
that fails closed, rejecting a genuine notification, not open).

Even after the local HMAC signature check passes, `SquareWebhookHandler`
still always re-confirms via an authenticated `GET /v2/payments/{id}`
(`verifyPayment()`) before marking an invoice paid — the same
belt-and-braces pattern used for every other gateway in this app.

Settings: `accessToken` + `locationId` (required by Square on every
Payment Link/Order — a Square merchant account can have multiple business
locations) + `webhookSecret` (the webhook subscription's own signature key
from the Square Developer Dashboard, distinct from `accessToken`) + a real
`sandbox` code branch (like PayPal).

## Regions

North America, Europe, Asia, Oceania —
`resources/gateway-status/gateways.json`. Square's well-established,
publicly documented merchant-eligible countries are the US, Canada, UK,
Ireland, France, Spain, Australia, and Japan — general public knowledge,
not independently re-verified against a primary source this session.

## ⚠️ Untested against a real account

Per the user's own statement, they have no registered company and cannot
create even a test Square Sandbox seller account — the same practical
barrier already hit with YooKassa, Paystack, Razorpay, and PayPal.
`sandbox_status` on the `/gateway-status` page stays `untested` permanently
for this reason.

## Verification

- Full-project Psalm (`vendor/bin/psalm --no-cache`): **no errors found**.
- Full Testo suite: all tests passing, including 21 new Square tests
  (`SquareSignatureServiceTest`, `SquarePaymentServiceTest`).
- Full PHPUnit suite (3,877 tests): all passing, after updating the legacy
  `PaymentRefundControllerTest` to pass the new `SquarePaymentService`
  constructor argument.
- Live-curled the new routes against the running local site
  (`http://invoice.myhost`): `squareWebhook` returns a clean `400` for an
  unsigned request; `squareInForm` returns a clean `404` for a nonexistent
  invoice url_key.
