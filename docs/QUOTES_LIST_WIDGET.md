# HTMX Quotes List Widget — Sort, Filter, Pagination, and Group-by

## Overview

The quote list (`quote/index`) supports HTMX-powered sort, filter, pagination, and
group-by: clicking a column header, submitting a filter, navigating to a page, or
changing the group-by selector sends an AJAX request and swaps only the grid `<div>`
in-place, without a full page reload.  The full-page render and the HTMX partial
render share a single source of truth — `QuotesListWidget`.

---

## 1. Architecture

| Layer | File | Role |
|---|---|---|
| Widget | `src/Invoice/Quote/Widget/QuotesListWidget.php` | Builds and renders the `GridView` with HTMX attributes, toolbar, filters, group-by, and page-size selector |
| Controller | `src/Invoice/Quote/QuoteController.php` | Declares `HtmlResponseFactory` constructor dep |
| Trait | `src/Invoice/Quote/Trait/Index.php` | Detects `Hx-Request` header; returns bare widget HTML for HTMX, full view otherwise |
| View | `resources/views/invoice/quote/index.php` | Breadcrumbs, modal, magnifier JS, grouping JS/CSS; delegates grid to widget |
| Utility | `src/Widget/PageSizeLimiter.php` | Static `buttons()` — page-size selector row in the grid summary |

---

## 2. Widget Pattern

`QuotesListWidget` extends `Yiisoft\Widget\Widget`.  Only auto-wirable dependencies
go in the constructor.  Cycle ORM repositories cannot be injected through the
`WidgetFactory` (it calls `$container->has()` which returns `false` for ORM-backed
repos), so they are passed via immutable setters:

```php
public function __construct(
    private readonly CurrentRoute $currentRoute,
    private readonly UrlGeneratorInterface $urlGenerator,
    private readonly TranslatorInterface $translator,
) {}

public function withPaginator(OffsetPaginator $paginator): static { ... }
public function withQR(QR $qR): static { ... }
public function withSoR(SOR $soR): static { ... }
public function withSR(SR $sR): static { ... }
public function withCsrf(string|\Stringable $csrf): static { ... }
// ...additional with*() setters
```

`withCsrf()` accepts `string|\Stringable` because the view renderer injects `$csrf`
as a `Yiisoft\Yii\View\Renderer\Csrf` object (which implements `Stringable`), while
the HTMX trait path passes a raw `string` cast from the request body.

---

## 3. Controller — HTMX Detection

```php
// src/Invoice/Quote/Trait/Index.php
if ($request->hasHeader('Hx-Request')) {
    return $this->htmlResponseFactory->createResponse(
        QuotesListWidget::widget()
            ->withPaginator($paginator)
            ->withQR($quoteRepo)
            ->withSoR($soR)
            ->withSR($sR)
            ->withCsrf((string) ($request->getParsedBody()['_csrf'] ?? ''))
            ->withDecimalPlaces((int) $sR->getSetting('tax_rate_decimal_places'))
            ->withVisible($sR->getSetting('columns_all_visible') === '1')
            ->withGroupBy($queryGroupBy ?? 'none')
            ->withClientCount($clientRepo->count())
            ->withGridSummary(...)
            ->withSortString($sortString)
            ->withOptionsDataClientsDropdownFilter(...)
            ->withOptionsDataStatusDropDownFilter(...)
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
    'hx-indicator'   => '#QuotesGridView',
    'hx-target'      => '#QuotesGridView',
    'hx-replace-url' => 'true',
    'hx-swap'        => 'outerHTML',
];

GridView::widget()
    ->containerAttributes(['id' => 'QuotesGridView', 'class' => 'position-relative'])
    ->sortableLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->filterFormAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    ->paginationWidget(
        OffsetPagination::widget()->addLinkAttributes(['hx-boost' => 'true', ...$htmxAttrs])
    )
```

`hx-boost="true"` on a sort or pagination `<a>` converts the navigation into an
AJAX GET.  The response (just the widget `<div>`) replaces the existing
`#QuotesGridView` element via `outerHTML`.  `hx-replace-url` keeps the address bar
in sync so the browser back button works correctly.

---

## 5. Group-by Feature

A `<select>` in the toolbar lets the user group quotes by status, client, client
group, month, year, date, or amount range.  The selected value is appended to the
URL as `?groupBy=<value>` (safe whitelist-validated by JavaScript before navigation).

Grouping is implemented via three focused private methods:

| Method | Responsibility |
|---|---|
| `makeGroupValueResolver(QR $qR, string $groupBy): \Closure` | Returns a closure that maps a `Quote` to its group key string |
| `computeGroupTotals(OffsetPaginator $paginator, callable $getGroupValue): array` | Iterates the page and builds `['count' => int, 'total' => float]` per group |
| `applyGrouping(GridView $gridView, ..., SR $sR): GridView` | Attaches a `beforeRow` callback that injects a collapsible header row when the group key changes |

Group header rows are rendered as `<tr class="group-header ...">` with toggle icons;
the collapse/expand JavaScript in `resources/views/invoice/quote/index.php` hides or
shows the member rows.

---

## 6. SonarQube Refactoring

Three SonarQube warnings (S138 function length, S3776 cognitive complexity, S107
parameter count) were resolved by splitting the original monolithic methods into
focused helpers.

### `buildToolbarString()` — 9 params → 6 params (S107)

`$translator`, `$urlGenerator`, and `$quoteIndex` were removed from the parameter
list.  The method now assigns them from `$this->translator`, `$this->urlGenerator`,
and the literal `'quote/index'` at the top of the body.

### `buildColumns()` — 221 lines / complexity 34 → ~30 lines / complexity ~1 (S138 + S3776)

All column content logic extracted into private helpers, each with ≤ 3 parameters:

| Helper | Column |
|---|---|
| `buildCheckboxColumn(TranslatorInterface $translator)` | Checkbox with tooltip on empty total |
| `buildActionColumn(UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator)` | View / edit / delete buttons |
| `buildStatusColumn(QR $qR, TranslatorInterface $translator)` | Status badge with dropdown filter |
| `buildSoColumn(SOR $soR, UrlGeneratorInterface $urlGenerator)` | Sales order link |
| `buildQuoteNumberColumn(UrlGeneratorInterface $urlGenerator, TranslatorInterface $translator)` | Quote number link with text filter |
| `buildClientColumn(TranslatorInterface $translator)` | Client name with dropdown filter |
| `buildTotalColumn(SR $sR, int $decimalPlaces, float $totalAmount)` | Amount label with footer total |

### `render()` — 167 lines / complexity 19 → ~117 lines / complexity ~8 (S138 + S3776)

The group-value resolver closure, the group-totals loop, and the `beforeRow` callback
were all extracted into `makeGroupValueResolver`, `computeGroupTotals`, and
`applyGrouping` respectively.

---

## 7. Files Changed

| File | Change |
|---|---|
| `src/Invoice/Quote/Widget/QuotesListWidget.php` | **Created** — HTMX-boosted `GridView`; immutable setters; group-by; refactored into focused private helpers |
| `src/Invoice/Quote/QuoteController.php` | Added `HtmlResponseFactory` constructor dep |
| `src/Invoice/Quote/Trait/Index.php` | Added `QuotesListWidget` import; HTMX branch returns bare widget HTML |
| `src/Invoice/Quote/Trait/OptionsData.php` | Added `@return` Psalm type annotations on `optionsDataClients()` and `optionsDataStatuses()` |
| `resources/views/invoice/quote/index.php` | All `use` imports moved before single `@var` docblock; widget call replaces inline `GridView`; breadcrumbs, modal, magnifier JS, and grouping JS/CSS retained |

---

## 8. Key Decisions

**Single source of truth** — `QuotesListWidget` renders identically whether called
from the full-page view or from the controller's HTMX branch.  No markup duplication.

**Immutable setters for ORM deps** — Cycle ORM-backed repositories are passed after
construction via `withX()` setters, bypassing the `WidgetFactory` DI resolution.

**`string|\Stringable` for CSRF** — The view renderer injects a `Csrf` object;
the trait path injects a raw string.  Widening the type covers both without casting.

**`HtmlResponseFactory` not `DataResponseFactoryInterface`** — The latter wraps the
response body in JSON encoding, which breaks HTMX's HTML swap.

**Whitelist-validated group-by navigation** — The JavaScript `change` handler on the
group-by `<select>` checks the selected value against an explicit allowed list before
setting `window.location.href`, preventing open-redirect manipulation.
