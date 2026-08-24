# Webshop Retail/Wholesale Distinction + Trade Pricing (August 2026)

Continuation of the in-process webshop merge
(`docs/WEBSHOP_INPROCESS_MERGE_AUGUST_2026.md`) on `feat/webshop-currency-api`.
Two related problems, both raised the same day: the public `/shop`
storefront and the staff/B2B side had no way to price the same catalog
`Product` differently, and — once that distinction existed — no way to
surface bulk/trade terms to a retail customer who wants to buy wholesale.

## Product model

Four new nullable/defaulted columns on `Product`
(`src/Infrastructure/Persistence/Product/Product.php`):

- `available_on_webshop` (bool, default `false`) — gates the public
  `/shop` catalog (`CatalogQueryService::listAll()`/`find()`). Has no
  effect on the staff invoice/quote/sales-order product picker
  (`ProductRepository::findAllPreloadedWithPrice()`), which still shows
  every priced product regardless — the two audiences were already
  using two different repository methods before this change, so the new
  `ProductWebshopQueryTrait::findAllPreloadedWithPriceAvailableOnWebshop()`
  slots in as a third, webshop-only variant rather than a parameter on
  the shared one.
- `retail_price` (nullable decimal) — the storefront's own sale price.
  Deliberately a separate column from `product_price` ("wholesale"),
  not a percentage markup/discount applied to it. Null/0.00 falls back
  to `product_price` via the new `Product::webshopPrice()` method (see
  below) rather than ever showing a misleading £0.00 listing.
- `trade_min_order_qty` / `trade_min_order_spend` (both nullable) —
  optional B2B ordering terms. Both null means "this product has no
  trade terms", which hides the storefront's Trade Pricing button
  entirely (`ProductListing::hasTradeTerms()`).

Staff-side UI (`resources/views/invoice/product/_form.php`): a Bootstrap
toggle-button pair — "B2B Client Portal (Wholesale)" / "Webshop
(Retail)" — bound to `available_on_webshop`
(`ProductFormFields::productAvailabilityField()`, hand-built
`Html::radio()` since `Field::radioList()` doesn't bind cleanly to a
`bool` property), plus plain text fields for `retail_price` and the two
trade-terms fields.

## Storefront: Trade Pricing button

`resources/views/shop/catalog/view.php` — when a product has trade
terms configured, a "Trade Pricing" button opens a `Yiisoft\Bootstrap5\
Modal` (matching the existing delivery-address-modal precedent, not
hand-rolled HTML) showing the trade price, minimum order quantity, and
minimum order spend, plus a "Request a Trade Quote" button. That button
deep-links into the existing `/interest` contact form
(`App\Contact\ContactController::interest()`) with the product's trade
details pre-filled into the `subject`/`body` query params — a new
`ContactForm::prefill()` method plus a GET-only read in the controller,
so a customer only has to add their name/email. This is deliberately
the entry point into the existing staff-driven B2B quote workflow, not
a new self-service quote-creation path — no anonymous webshop visitor
can create a real `Quote` directly (that requires a `Client`), so a
lead captured this way is followed up by staff the same way any other
contact-form enquiry is.

Caught live: `Yiisoft\Bootstrap5\Modal::render()` throws
`InvalidArgumentException` ("Set the trigger button before rendering
the modal") unless `->triggerButton()` is called — an initial version
built the trigger button by hand and rendered it as a separate sibling
element, which 500'd in production-style error pages (confirmed via
`runtime/logs/app.log`, not visible in the browser without `YII_DEBUG`).
Fixed by passing the pre-built `Button` object into `->triggerButton()`
instead, matching how `resources/views/layout/templates/storefront/
main.php`'s own delivery-address modal already does it.

## Billing price bug: `product_price` was leaking into paid invoices

`App\Api\OrderService::addOrderItem()` — the webshop checkout → `InvItem`
conversion — priced every order line at `$product->getProductPrice()`
("wholesale"), never `retail_price`, even after `retail_price` started
being the price actually shown on the storefront. A customer could be
shown one price on `/shop` and charged a different one on the resulting
invoice.

Fixed with a single source of truth, `Product::webshopPrice()`
(`retail_price` when actually set, else `product_price`), used
identically by both `CatalogQueryService::toListing()` (what the
customer sees) and `OrderService::addOrderItem()` (what the `InvItem`
is actually billed at) — the two must never diverge again. Every
staff/B2B price-reading call site (`ProductSelectionController`,
`InvRecurringCronService`, `ProductsListWidget`) was audited via grep
and confirmed to still read `product_price` directly, unchanged.

A new `OrderServiceTest::chargesTheInvItemAtWebshopPriceNotWholesale
ProductPrice()` forces `retail_price` ≠ `product_price` and asserts the
`InvItem` is billed at the retail figure via a Mockery argument matcher
on `InvItemService::addInvItemProduct()`.

## Data migration

The Cycle ORM schema sync (`BUILD_DATABASE=true` cycle) had already run
by the time this was verified; the 5 seed webshop products (ids
150–154) needed `available_on_webshop` backfilled to `1` by hand — the
new column defaults `false`, so without the backfill they would have
silently vanished from `/shop` the moment the gating went live.
`retail_price` was left `NULL` for all 5 (falls back to `product_price`
via `webshopPrice()`, so their displayed/billed price is unchanged).

## Same-day continuation, same branch

A number of smaller fixes landed earlier the same day on this branch,
not separately documented until now:

- Flash messages leaking between the storefront and staff layouts —
  new `FlashScope` enum, per-key `Flash::get()` reads instead of
  `getAll()`.
- A cart/checkout product gallery so a customer can add more items
  without leaving checkout.
- A "Webshop" nav link on the staff-facing `soletrader/main.php` layout,
  gated by the same `no_front_webshop_page` setting every other
  front-page marketing link already uses.
- A dev-only info tooltip on the storefront currency dropdown
  (`SettingTooltipTrait`), including a real `Html::encode()` is
  `ENT_NOQUOTES` attribute-injection bug found and fixed along the way.
- Live/auto-updating exchange rates (`ExchangeRateUpdateService`,
  Frankfurter/ECB API) via a new `ExchangeRateAutoUpdateMiddleware` on
  every `/invoice/*` request, plus a manual "Refresh now" button.
- `inv/view`'s breadcrumbs hidden from observers (webshop customers),
  replaced with a 3-step "Checkout → Sent → Pay Now" stepper.
- Quote/SalesOrder nav dropdowns hidden in `guest.php` for observers
  with no quotes/sales orders assigned to them at all — a data-driven
  check (`GuestLayoutViewParameters`), not a new account-type flag.
- The CRON key regenerate button on Settings finally sitting inside its
  own text field without clipping — root cause was a too-narrow
  `col-md-6` column, not (as first assumed) a z-index/stacking issue.

## Verified

Full-project `vendor/bin/psalm --no-cache`: no errors (165 pre-existing
info-level hints, unchanged count). Full Testo suite: 943/943 passing,
zero notices — includes new/updated coverage in `ProductEntityTest`
(`webshopPrice()` fallback), `CatalogQueryServiceTest` (retail/wholesale/
trade-terms mapping, `available_on_webshop` gating), and `OrderServiceTest`
(billing price). Live-verified: the DB schema and backfill directly
against the local MySQL instance; the storefront product page and the
Trade-Pricing → Request-a-Trade-Quote → prefilled contact form chain via
curl end-to-end; the staff product edit form (all 4 new fields render
correctly, no console/PHP errors) via a logged-in Playwright pass. The
form's save round-trip itself was not independently re-confirmed after
that pass; `ProductService::applyProductCoreFields()` follows the exact
existing `isset()`-ternary shape already proven for `product_price`/
`purchase_price`.
