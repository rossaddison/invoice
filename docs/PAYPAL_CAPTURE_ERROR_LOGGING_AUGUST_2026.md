# PayPal Structured Error Logging (August 2026)

## The incident

Live sandbox testing of the PayPal gateway hit a real `captureOrder()`
failure: retrying payment on an invoice that had *already* been
successfully paid (the first webhook notification failed to deliver due
to an unrelated URL misconfiguration, so this app's own database never
learned the invoice was paid) got rejected by PayPal with HTTP 422.

The only way to find out *why* was manually digging through PayPal's own
Developer Dashboard → Apps → Error Logs, clicking into the specific
Debug ID, and reading the expanded detail there:

```
Issue: DUPLICATE_INVOICE_ID
Description: The requested action could not be performed, semantically
incorrect, or failed business validation.
```

Nothing in this app's own logs said anything more useful than "response
missing capture id or status" — the actual PayPal-provided reason was
being silently discarded.

## Root cause

`PaypalPaymentService::captureOrder()` calls `POST
/v2/checkout/orders/{id}/capture` with `'http_errors' => false`, so a
rejected capture arrives as a normal PSR-7 response with a 4xx/5xx status
— not an exception. `parseCaptureOrderResponse()` only knew how to read
a *successful* capture's shape
(`purchase_units[].payments.captures[].{id,status}`); PayPal's error
shape (`{name, message, debug_id, details: [{issue, description}]}`)
doesn't match that, so it fell through to a generic "missing capture id
or status" warning with no further detail.

The same gap existed, to varying degrees, in every other PayPal call in
the class:
- `refund()`'s `parseRefundResponse()` already special-cased the error
  shape (`isset($data['name'])`) enough to return a sensible message to
  the *caller*, but never logged the detail anywhere.
- `createPayment()`, `verifyPayment()`, `verifyWebhookSignature()`, and
  `accessToken()` all just logged `$e->getMessage()` from the caught
  `GuzzleException` — which, since none of those calls set
  `http_errors: false`, does throw on a 4xx/5xx, but the exception
  message is an unstructured, sometimes-truncated string, not the parsed
  `issue`/`debug_id` fields that actually pinpoint the cause.

## Fix

Added one shared private helper,
`PaypalPaymentService::extractErrorDetail(string $rawBody): array{name,
message, issue, debug_id}`, that parses PayPal's structured error shape
once. `issue` and `debug_id` are the fields that actually matter —
`message` alone is frequently just PayPal's generic wrapper text ("The
requested action could not be performed, semantically incorrect, or
failed business validation.") for *any* 422, regardless of cause.

Wired into every failure path in the class:

- `parseCaptureOrderResponse()`: checks `$response->getStatusCode() >=
  400` before attempting to read a capture shape, and logs the full
  extracted detail (`order_id`, `http_status`, `name`, `message`,
  `issue`, `debug_id`) via `$this->logger->error(...)`.
- `parseRefundResponse()`: same detail now logged alongside the existing
  message returned to the caller.
- `createPayment()`/`verifyPayment()`/`verifyWebhookSignature()`/
  `accessToken()`: a new `errorLogContext(GuzzleException|JsonException):
  array` helper wraps the caught exception's own message with the
  extracted detail, when the exception is a `RequestException` carrying
  a response (i.e. an actual HTTP error, not a connection failure).

No behavior change to what any method *returns* — `captureOrder()` still
returns `null` on this class of failure, `refund()` still returns the
same `PaymentRefundResult`. This is purely additive: the same failures
now leave a diagnosable trail in this app's own logs instead of requiring
a trip to PayPal's dashboard.

## Verification

Full-project `vendor/bin/psalm --no-cache`: no errors. Full Testo suite:
805/805 passing (802 existing + 3 new — `captureOrderLogsPaypalsIssueAndDebugIdWhenRejected`,
`refundLogsPaypalsIssueAndDebugIdWhenRejected`, and
`createPaymentLogsPaypalsIssueAndDebugIdWhenRejectedWithAnErrorStatus` —
each asserts, via a real Mockery expectation on `LoggerInterface`, not
just a spy, that `issue`/`debug_id` land in the logged context array for
a mocked PayPal error response shaped exactly like the real
`DUPLICATE_INVOICE_ID` one hit live).
