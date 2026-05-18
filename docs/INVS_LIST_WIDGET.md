# HTMX Invoices List Widget — Sort, Filter, Pagination, and Group-by

## Overview

The invoice list (`inv/index`) supports HTMX-powered sort, filter, pagination, and
group-by: clicking a column header, submitting a filter, navigating to a page, or
changing the group-by selector sends an AJAX request and swaps only the grid `<div>`
in-place, without a full page reload.  The full-page render and the HTMX partial
render share a single source of truth — `InvsListWidget`.

---

## 1. Architecture

| Layer | File | Role |
|---|---|---|
| Widget | `src/Invoice/Inv/Widget/InvsListWidget.php` | Builds and renders the `GridView` with HTMX attributes, toolbar, filters, group-by, and page-size selector |
| Controller | `src/Invoice/Inv/InvController.php` | Declares `HtmlResponseFactory` constructor dep |
| Trait | `src/Invoice/Inv/Trait/Index.php` | Detects `Hx-Request` header; returns bare widget HTML for HTMX, full view otherwise |
| View | `resources/views/invoice/inv/index.php` | Breadcrumbs, modals, magnifier JS, grouping JS/CSS; delegates grid to widget |
| Utility | `src/Widget/PageSizeLimiter.php` | Static `buttons()` — page-size selector row in the grid summary |
| Utility | `src/Widget/GridComponents.php` | `gridMiniTableOfInvSentLogsForInv()` — inline sent-log mini-table |

---

## 2. Widget Pattern

`InvsListWidget` extends `Yiisoft\Widget\Widget`.  Only auto-wirable dependencies
go in the constructor.  Cycle ORM repositories cannot be injected through the
`WidgetFactory` (it calls `$container->has()` which returns `false` for ORM-backed
repos), so they are passed via immutable setters:

```php
public function __construct(
    private readonly CurrentRoute $currentRoute,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly TranslatorInterface $translator,
    private readonly GridComponents $gridComponents,   // invoice-specific
) {}

public function withPaginator(OffsetPaginator $paginator): static { ... }
public function withIR(IR $iR): static { ... }
public function withIrR(IRR $irR): static { ... }
public function withIslR(ISLR $islR): static { ... }
public function withQR(QR $qR): static { ... }       // optional — quote-link column
public function withSoR(SOR $soR): static { ... }    // optional — SO-link column
public function withDlR(DLR $dlR): static { ... }    // optional — delivery-location column
public function withSR(SR $sR): static { ... }
public function withCsrf(string|\Stringable $csrf): static { ... }
// ...additional with*() setters
```

`withCsrf()` accepts `string|\Stringable` because the view renderer injects `$csrf`
as a `Yiisoft\Yii\View\Renderer\Csrf` object (which implements `Stringable`), while
the HTMX trait path passes a raw `string` cast from the request body.

`QR`, `SOR`, and `DLR` are optional — their columns are appended only when the
corresponding dependency has been injected.  The guard in `buildColumns()`:

```php
if ($this->qR !== null) {
    $columns[] = $this->buildQuoteLinkColumn($this->qR);
}
```

`render()` requires `paginator`, `iR`, `irR`, `islR`, and `sR` to be non-null and
returns `''` immediately if any of them is missing.

---

## 3. Controller — HTMX Detection

```php
// src/Invoice/Inv/Trait/Index.php
if ($request->hasHeader('Hx-Request')) {
    return $this->htmlResponseFactory->createResponse(
        InvsListWidget::widget()
            ->withPaginator($paginator)
            ->withIR($invRepo)
            ->withIrR($irR)
            ->withIslR($islR)
            ->withQR($qR)
            ->withSoR($soR)
            ->withDlR($dlR)
            ->withSR($this->sR)
            ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
            ->withDecimalPlaces((int) $this->sR->getSetting('tax_rate_decimal_places'))
            ->withVisible($visible !== '0')
            ->withVisibleInvSentLogColumn($visibleToggleInvSentLogColumn !== '0')
            ->withGroupBy($queryGroupBy ?? 'none')
            ->withClientCount($clientRepo->count())
            ->withGridSummary($gridSummary)
            ->withSortString($sortString)
            ->withLabel($label)
            ->withOptionsInvNumberDropDownFilter(...)
            ->withOptionsCreditInvNumberDropDownFilter(...)
            ->withOptionsFamilyNameDropDownFilter(...)
            ->withOptionsClientsDropDownFilter(...)
            ->withOptionsClientGroupDropDownFilter(...)
            ->withOptionsYearMonthDropDownFilter(...)
            ->withOptionsStatusDropDownFilter(...)
            ->render()
    );
}
```

`HtmlResponseFactory` (`Yiisoft\DataResponse\ResponseFactory\HtmlResponseFactory`)
sends the raw HTML string.  **Do not** use `DataResponseFactoryInterface` here — it
JSON-encodes the response body, breaking HTMX.

PSR-7 headers are case-insensitive, so `hasHeader('Hx-Request')` matches the
`HX-Request: true` header that HTMX sends with every XHR.

---

## 4. HTMX Attributes on the Grid

```php
$htmxAttrs = [
    'hx-indicator'   => '#InvsGridView',
    'hx-target'      => '#InvsGridView',
    'hx-replace-url' => 'true',
    'hx-swap'        => 'outerHTML',
];

GridView::widget()
    ->containerAttributes(['id' => 'InvsGridView', 'class' => 'position-relative'])
    ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->paginationWidget(
        OffsetPagination::widget()->addLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    )
```

`hx-boost="true"` on a sort or pagination `<a>` converts the navigation into an
AJAX GET.  The response (just the widget `<div>`) replaces the existing
`#InvsGridView` element via `outerHTML`.  `hx-replace-url` keeps the address bar
in sync so the browser back button works correctly.

---

## 5. Column Builders

Every column is extracted into a focused private helper with ≤ 3 explicit parameters.
`$this->translator`, `$this->urlGenerator`, and `$this->sR` are captured from the
instance inside each method to keep signatures short.

| Helper | Column | Notes |
|---|---|---|
| `buildCheckboxColumn()` | Checkbox | Disabled when invoice total = 0; tooltip hint |
| `buildEditColumn(SR $sR)` | Edit / read-only button | 3-dim `$map` keyed `[$isReadOnly][$disableReadOnly][$statusId]` (see §6) |
| `buildPdfEmailColumn()` | PDF download + email | Dropdown with two PDF variants and email button (disabled for drafts) |
| `buildDeleteColumn(SR $sR)` | Delete | Disabled when `isReadOnly`, `disable_read_only='0'` is false, or invoice originates from a SO/quote |
| `buildInvNumberColumn()` | Invoice number | Links to `inv/view`; dropdown filter; shows first item family/product name |
| `buildFamilyNameColumn()` | Family name | Shows `getFirstItemFamilyName()`; dropdown filter |
| `buildDateCreatedYearMonthColumn()` | Date created | Dropdown filter by `Y-m` |
| `buildStatusColumn(IR, IRR, SR)` | Status badge | Bootstrap badge coloured by status class; appends 🚫 if read-only locked; appends ♻️ if has recurring schedule |
| `buildClientActiveColumn()` | Client active | Links to `client/edit`; ✅/❌ for active/inactive |
| `buildCreditNoteColumn(IR)` | Credit note | Links back to parent invoice number; dropdown filter by parent invoice number |
| `buildSentLogToggleColumn(ISLR)` | Sent-log toggle | Shows link to `setting/toggleinvsentlogcolumn` when emails > 0, else `0 📧` |
| `buildSentLogCountColumn(ISLR)` | Sent-log count | Shows count as link to `invsentlog/index` filtered by invoice number; `visible` flag |
| `buildSentLogTableColumn(ISLR)` | Sent-log mini-table | Loads logs, hydrates `Inv::addInvSentLog()`, renders via `GridComponents::gridMiniTableOfInvSentLogsForInv()`; `visibleInvSentLogColumn` flag |
| `buildClientColumn()` | Client name | Dropdown filter |
| `buildClientGroupColumn()` | Client group | Dropdown filter |
| `buildTotalColumn(SR, int, float)` | Total amount | Text-input filter; footer sum for current page |
| `buildPaidColumn(SR, int, float)` | Paid amount | Text-input filter; danger label when paid < total; footer sum |
| `buildBalanceColumn(SR, int, float)` | Balance | Text-input filter; success/warning label; footer sum |
| `buildDeliveryAddColumn()` | Add delivery | `+` link to `del/add`; `visible` flag |
| `buildQuoteLinkColumn(QR $qR)` | Quote link | Optional; only added when `$this->qR !== null` |
| `buildSoLinkColumn(SOR $soR)` | Sales order link | Optional; only added when `$this->soR !== null` |
| `buildDeliveryLocationColumn(DLR $dlR)` | Delivery location GLN | Optional; only added when `$this->dlR !== null` |

Inline `DataColumn` instances (not extracted) cover: client number, street address,
street address 2, time created, date modified, and due date.

---

## 6. Edit Column — Read-only / Disable-Read-Only Matrix

The edit button content, URL, and HTML attributes each depend on three flags:

| Key | Source | Values |
|---|---|---|
| `$ro` | `$inv->getIsReadOnly()` | `'true'` / `'false'` |
| `$dRO` | `$sR->getSetting('disable_read_only')` | `'0'` / `'1'` |
| `$st` | `(string) $inv->reqStatusId()` | `'1'` (draft) / `'2'` (sent) |

Three separate `/** @psalm-var array<string, array<string, array<string, ...>>> $map */`
closures implement this lookup for `content`, `url`, and `attributes` respectively.
If the combination falls outside the matrix, sensible defaults apply (`'🚫'`, `''`,
`[]`).

---

## 7. Toolbar

`buildToolbarString(bool $enableGrouping): string` builds a Bootstrap 5 button group
containing:

- **Reset** (`btn-reset`) — navigates to the current route, clearing all filters
- **↔️ Hide/Unhide columns** (`btn-all-visible`) — links to `setting/visible?origin=inv`
- **☑️ Copy invoice** (`btn-modal-copy-inv-multipe`) — opens `#modal-copy-inv-multiple`
- **☑️ Mark as sent** (`btn-mark-as-sent`)
- **☑️ Mark sent as draft** (`btn-mark-sent-as-draft`) — disabled when `disable_read_only='0'`
- **☑️ Create recurring** — opens `#create-recurring-multiple`
- **➕ Add invoice** — opens `#modal-add-inv`; disabled with tooltip when `clientCount = 0`
- **Group-by `<select>`** — 8 options (none, status, client, client_group, month, year, date, amount_range)
- **Collapse / Expand all groups** — shown only when `$enableGrouping` is true

---

## 8. Group-by Feature

A `<select>` in the toolbar lets the user group invoices by status, client, client
group, month, year, date, or amount range.  The selected value is appended to the
URL as `?groupBy=<value>` (safe whitelist-validated by JavaScript before navigation).

Grouping is implemented via three focused private methods:

| Method | Responsibility |
|---|---|
| `makeGroupValueResolver(string $groupBy): \Closure` | Returns a closure `fn(Inv): string` that maps an invoice to its group key; uses `$this->iR` for status label lookup |
| `computeGroupTotals(OffsetPaginator $paginator, callable $getGroupValue): array` | Iterates the page and builds `['count' => int, 'total' => float, 'paid' => float, 'balance' => float]` per group |
| `applyGrouping(GridView $gridView, ..., int $columnCount): GridView` | Attaches a `beforeRow` callback that injects a collapsible header row when the group key changes |

Unlike `QuotesListWidget` (which tracks only `count` and `total`), the invoice
widget accumulates `paid` and `balance` per group.  The group header row shows all
three sums alongside the count badge.

### Group keys

| `groupBy` value | Key source |
|---|---|
| `client` | `$invoice->getClient()?->getClientFullName()` or `'Unknown Client'` |
| `status` | `$iR->getSpecificStatusArrayLabel((string) $invoice->reqStatusId())` |
| `month` | `$invoice->getDateCreated()->format('Y-m')` |
| `year` | `$invoice->getDateCreated()->format('Y')` |
| `date` | `$invoice->getDateCreated()->format('Y-m-d')` |
| `client_group` | `$invoice->getClient()?->getClientGroup()` or `'No Group'` |
| `amount_range` | `< $100` / `$100–$500` / `$500–$1000` / `> $1000` |
| _(any other)_ | `'No Group'` |

`makeGroupValueResolver` takes only `string $groupBy` (no repository parameter); it
asserts `$this->iR !== null` internally.  This differs from `QuotesListWidget`, which
passes `QR $qR` explicitly.

Group header rows are rendered as
`<tr class="group-header bg-secondary text-white fw-bold group-collapsible">` with a
chevron toggle icon; the collapse/expand JavaScript in `index.php` hides or shows the
member rows.

---

## 9. Visibility Flags

| Flag | Setter | Effect |
|---|---|---|
| `$visible` | `withVisible(bool)` | Toggles `table-responsive` wrapper and makes optional extended columns visible |
| `$visibleInvSentLogColumn` | `withVisibleInvSentLogColumn(bool)` | Makes the sent-log mini-table column visible |

Both flags are driven by settings `columns_all_visible` and
`column_inv_sent_log_visible` respectively.

---

## 10. Files Changed

| File | Change |
|---|---|
| `src/Invoice/Inv/Widget/InvsListWidget.php` | **Created** — HTMX-boosted `GridView`; immutable setters; group-by; sent-log columns; edit read-only matrix; optional QR/SOR/DLR columns |
| `src/Invoice/Inv/InvController.php` | Added `HtmlResponseFactory` constructor dep |
| `src/Invoice/Inv/Trait/Index.php` | Added `InvsListWidget` import; HTMX branch returns bare widget HTML; `max(1, ...)` fix for `int<1, max>` Psalm requirement |
| `src/Invoice/Inv/Trait/OptionsData.php` | Added `@psalm-return array<array-key, string>` annotations on all seven `optionsData*Filter()` methods |
| `resources/views/invoice/inv/index.php` | Rewritten from ~2115 to ~767 lines; all inline `GridView` construction removed; breadcrumbs, modals, magnifier JS, mobile preview JS, and grouping JS/CSS retained |
| `Tests/Unit/Invoice/Widget/InvsListWidgetTest.php` | **Created** — 44 tests covering render() guard, immutable setters, CSRF widening, all `makeGroupValueResolver` branches, and `computeGroupTotals` aggregation |

---

## 11. Key Decisions

**Single source of truth** — `InvsListWidget` renders identically whether called
from the full-page view or from the controller's HTMX branch.  No markup duplication.

**`GridComponents` in the constructor** — Unlike `QuotesListWidget`, the invoice
widget needs `GridComponents` to render the inline sent-log mini-table.  It is
auto-wirable and belongs in the constructor rather than as an immutable setter.

**Immutable setters for ORM deps** — Cycle ORM-backed repositories are passed after
construction via `with*()` setters, bypassing the `WidgetFactory` DI resolution.

**Optional QR / SOR / DLR** — Quote-link, sales-order-link, and delivery-location
columns are only appended when the corresponding repository has been injected; the
widget renders cleanly without them.

**Three financial totals per group** — Invoices track paid and balance separately
from total.  The group header displays all three so the user can see outstanding
balance at a glance without opening each invoice.

**3-dim `$map` in the edit column** — The read-only / disable-read-only / status
combination produces four distinct edit-button states.  A nested array lookup
(`$map[$ro][$dRO][$st]`) is more legible than a chain of `if/elseif` and easier to
extend when new statuses are added.

**`string|\Stringable` for CSRF** — The view renderer injects a `Csrf` object;
the trait path injects a raw string.  Widening the type covers both without casting.

**`HtmlResponseFactory` not `DataResponseFactoryInterface`** — The latter wraps the
response body in JSON encoding, which breaks HTMX's HTML swap.

**Whitelist-validated group-by navigation** — The JavaScript `change` handler on the
group-by `<select>` checks the selected value against an explicit allowed list before
setting `window.location.href`, preventing open-redirect manipulation.
