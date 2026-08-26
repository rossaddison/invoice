# Reorder-Threshold Stock — Telegram Alert + Checkout Enforcement (August 2026)

`Product.stock_quantity` has been a ledger-backed cache since the
stock-movement feature (`docs/STOCK_MOVEMENT_LEDGER_AND_WEBSHOP_API_AUGUST_2026.md`),
but nothing consumed it: no staff UI, no low-stock signal, and the
storefront never referenced stock at all. Design settled through
discussion: a reserved `reorder_threshold` buffer, not a plain low-stock
flag — physically in stock but never shown or sold to the public.
Customer-facing "stock left" = `stock_quantity - reorder_threshold`,
floored at 0.

## Single source of truth

`Product.reorder_threshold` (nullable `decimal(20,2)`) +
`Product::availableStock()` on `ProductTrait4` — null when untracked,
otherwise the floored subtraction. Every consumer below calls this one
method, so the reserved buffer can never drift between the staff view,
the storefront, and checkout enforcement.

## Staff UI

Staff Product form gains `track_stock`/`reorder_threshold` fields, plus
a read-only current-stock display — `track_stock`/`stock_quantity`
existed on the entity but were never in the staff UI at all before this.

## Low-stock alert

New `LowStockNotifier`: fires a Telegram alert (via the existing
`TelegramHelper::sendMessage()`) only on the actual crossing (not every
subsequent sale while already low), reusing `enable_telegram`/
`telegram_token`/`telegram_chat_id`. Never throws — runs inside
`InvPaymentSettlementService`'s DB transaction, wired via a new
`InvPaymentSettlementDeps` property.

## Storefront

`ProductListing`/`CatalogQueryService` carry `availableStock`;
`shop/catalog/view.php` shows the real count and disables add-to-cart at
0.

## Cart-level clamping (UX only)

`CartController` caps `add()`/`update()` at `availableStock` and
flashes/JSON-flags when clamped — `cart.ts` surfaces it on the quantity
input.

## Checkout-level enforcement (authoritative)

New `InsufficientStockException` thrown from
`OrderService::addOrderItem()`, caught around the existing
`withTransaction()` call in `createInvoiceAndItems()` and turned into
the same null return every other failure path there already produces —
flows straight into `CheckoutController`'s existing `checkout.failed`
handling, no new branching. The DB transaction's own rollback-on-
exception undoes any invoice items already written earlier in the same
loop. The cart-level clamp above is UX only; this is the real,
authoritative guarantee that stock can't be oversold.

## Verified

18 new Testo tests (`Product::availableStock()` edge cases,
`LowStockNotifier`'s guard logic, `OrderService`'s insufficient-stock
rollback, `InvPaymentSettlementService`'s before-sale notifier call,
`CartController`'s clamping in both `add()`/`update()`) plus fixture
fixes in 2 pre-existing test files whose `Product` mocks now need
`availableStock()`/`stock_quantity` stubbed. Full Testo Unit suite:
989 passed (up from 971). Full-project Psalm `--no-cache`: no errors
found. Schema synced live (`BUILD_DATABASE` cycle, confirmed via direct
query against `yii3_i.product`). DI graph confirmed via `php yii list`.

Real Telegram delivery not live-verified in this environment (no
configured bot token/chat) — `LowStockNotifier`'s own tests cover every
guard up to that boundary; the actual send is this app's existing,
unchanged `TelegramHelper::sendMessage()` call.
