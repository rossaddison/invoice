# Stock Availability Surfaced in Every Staff Product-Entry Point (August 2026)

`Product::availableStock()` (`docs/REORDER_THRESHOLD_STOCK_AUGUST_2026.md`) has
been the single source of truth for sellable stock since the reorder-threshold
feature, but it was only ever wired into the public `/shop` storefront and
checkout. Every staff-facing way of putting a product onto an Invoice or Quote
— the "Choose Items" modal and the manual add/edit line-item forms — had no
visibility into stock at all and no check of any kind. A product with zero
stock (or already inside its own `reorder_threshold` buffer) could be added
exactly like one with plenty.

Unlike checkout, staff routinely have legitimate reasons to invoice or quote
past current stock — backorders, stock corrections not yet entered, services
mismarked as tracked — so this is deliberately **informational, not a block**.
The item is always added; staff just get to see the shortfall.

## Scope of the asymmetry

Invoices get both visibility and a post-add warning. Quotes get visibility
only — a Quote is non-binding (no obligation, stock may replenish before
conversion), so no warning is raised there.

## "Choose Items" modal (`ProductSelectionController`)

`resources/views/invoice/product/_partial_product_table_modal.php` (shared by
both the Inv and Quote pickers) gained an "Available Stock" column —
`Product::availableStock()`, a dash for untracked products, highlighted red
at/below zero.

`ProductSelectionController::selectionInv()` checks each selected product's
`availableStock()` against the quantity about to be added (a new shared
`defaultQuantityFor()` helper replaces three copies of the same inline
ternary). Items are still added; if any were short, one `flashMessage()`
warning lists which products and their available count — shown on the page
reload the modal's own JS already triggers. `selectionQuote()` is untouched.

## Manual add/edit line-item forms

`_item_form_product.php` (add) and `_item_edit_product.php` /
`_item_edit_form_product.php` (edit), for both Inv and Quote, now suffix each
product `<option>` with its available stock, e.g. `Widget (Available Stock:
3)` — untracked products are unchanged.

`InvItemHtmxController::addProduct()` (the manual add form's real target —
the form's `actionName` is `invitemhtmx/addProduct`) additionally runs the
same insufficient-stock check via a new `stockWarningFor()` helper. This form
is HTMX (`hx-post` targeting `#partial_item_table_parameters`, `innerHTML`
swap, no full page load), so a session flash message wouldn't be visible
until some later navigation — instead the warning string is threaded through
`renderPartial()` into `partial_item_table.php`, which renders it as a
dismissable Bootstrap `Alert::widget()->variant(AlertVariant::WARNING)`
directly above the items table, inside the same swapped fragment. The
equivalent Quote HTMX controller is untouched (no warning, per the
Invoice/Quote asymmetry above); the edit-existing-line flows
(`InvItemController::editProduct()`, `QuoteItemController::editProduct()`)
got the dropdown visibility only, not a save-time warning — a different
(non-HTMX, full POST/redirect) code path not covered by this pass.

## Translations

Two new keys in `resources/messages/en/app.php`:
`product.available.stock` ("Available Stock") and
`product.added.despite.insufficient.stock` ("Added despite insufficient
stock:").

## Verified

Psalm `--no-cache` clean on every touched file. Full Testo Unit suite
unaffected: 1004/1004, no new skip/incomplete/notice markers. No existing
test targeted `ProductSelectionController`, `InvItemHtmxController`, or any
of the touched view partials before this change, so none were added —
flagged rather than assumed sufficient.
