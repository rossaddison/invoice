# Stock Movement Ledger + Webshop API Auth (August 2026)

Two related pieces of work: a ledger-based inventory feature triggered by
invoice payment, and the API-key-authenticated `/api` surface that the
planned headless webshop storefront needs (design settled earlier in
[`WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md`](WEBSHOP_HEADLESS_STOREFRONT_DESIGN_AUGUST_2026.md)).
The API work exists specifically to unblock that design's first two "not
yet started" steps.

## Stock Movement — ledger, not a mutable counter

`Product.stock_quantity` is a derived cache, never written to directly
except by recomputing it from the ledger — the same relationship
`InvAmount` already has to the `InvItemAmount` rows that produce it. The
ledger itself is a new `StockMovement` entity
(`src/Infrastructure/Persistence/StockMovement/StockMovement.php`,
`src/Invoice/StockMovement/StockMovementRepository.php`): append-only
rows of `product_id`, a `StockMovementType` (`Sale`, `Return`, `Receipt`,
`Adjustment`, `StocktakeCorrection`), a signed `quantity_delta`, and an
optional `inv_id` back-reference. `Product` gained `track_stock` (bool,
default `true`) and `stock_quantity` (decimal(20,2), default `0.00`).

**The trigger is invoice *paid*, not *sent*.** This went through one
correction mid-design — the first instinct was "sent", since that's when
a customer commits to an order, but stock should only actually leave the
warehouse once payment is confirmed, matching how this app already treats
"paid" as the point of no return elsewhere (refund eligibility, PDF
finalization).

## `InvPaymentSettlementService` — one call site, seventeen gateways

`markInvoicePaidAndAdjustStock()`
(`src/Invoice/Inv/InvPaymentSettlementService.php`) intentionally has the
longer, more explicit name over something like `settle()` — the whole
point of the method is legible at the call site. Inside one
`InvService::withTransaction()` block: sets the invoice to paid
(idempotent — returns immediately if already status 4), settles the
`InvAmount` record, then writes one `StockMovement` per tracked `InvItem`
and updates that `Product.stock_quantity` cache.

Signature deliberately takes the already-loaded `Inv`/`InvAmount` objects
rather than re-fetching by ID — every one of the 17 gateway call sites
already has both loaded, sometimes already partially mutated, and a
re-fetch would have raced against that. Caught before it shipped by
reading each webhook handler's actual code before wiring, not assumed
from the method's own shape.

Wired into every payment gateway's webhook handler
(`src/Invoice/PaymentInformation/Service/*WebhookHandler.php` — Adyen,
AmazonPay, CheckoutCom, GoCardless, MercadoPago, Mollie, Paypal, Paystack,
Razorpay, Robokassa, Square, Stripe, TrueLayer, Yookassa) plus
`PaymentInformationController`'s Braintree POST handler and legacy Mollie
reverse-lookup fallback — each one's previously-duplicated
status/amount-update block replaced with a single call.

## Webshop API auth

Three new pieces on the `invoice` side, gating a small, deliberately
narrow `/api` surface:

- **`ApiClient`** (`src/Infrastructure/Persistence/ApiClient/ApiClient.php`)
  — persistence entity holding a hashed API key per external caller.
- **`ApiKeyAuthMiddleware`** (`src/Middleware/ApiKeyAuthMiddleware.php`)
  — gates `GET /api/products` (+ `/api/products/{id}`) and
  `POST /api/orders`, registered per-route in
  `config/common/routes/routes.php` rather than through the session/cookie
  `RoutePermission` gate the rest of `/api` uses, since an externally
  deployed caller has no browser session to present.
- **`api-client/generate`** console command
  (`src/Api/Console/GenerateApiKeyCommand.php`) to issue new keys.

`POST /api/orders` (`OrdersController` → `OrderService`,
`src/Api/OrderService.php`) creates a guest order: finds-or-creates a
`Client` by checkout email (`ClientRepository::findByEmail()`, new), then
an `Inv` + `InvItem`s from the cart, mirroring the shape
`HomeCareSignupController::createInvoice()` already uses for its own
guest-invoice creation. Item prices are always the product's own current
`product_price`, never trusted from the request body, so a tampered cart
payload can change quantities but never the per-unit charge. The
"creating user" for an order with no logged-in session at all is resolved
the same way `InvRecurringCronService::resolveAdminUser()` does for its
own unattended context — the first admin-type `UserInv` on file.
`/api/orders` is added to `CsrfExemptMiddleware`'s allowlist since it's
already gated by the API key, not a browser session.

## Verified

880/880 Testo, 3912/3912 legacy Codeception/PHPUnit, full-project Psalm
clean throughout. New coverage: `InvPaymentSettlementServiceTest.php`,
`StockMovementTest.php`, `ApiKeyAuthMiddlewareTest.php`, plus the three
legacy webhook handler tests (Stripe/Adyen/GoCardless) updated for the
new constructor dependency. Not yet tested against a real payment gateway
sandbox end-to-end, or against a real external caller hitting `/api` —
both gateways' webhook flow and the API endpoints have only been
exercised through mocked tests so far.
