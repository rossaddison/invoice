# Dropdown Filter Combining — `inv/index`, `quote/index`, `salesorder/index`, `product/index`

## How Filtering Works On These Grids

Each grid's `GridView` renders one shared hidden `<form>` per grid — every column's filter
`<select>` is associated to it via HTML's `form="..."` attribute, and each dropdown's
`onChange="this.form.submit()"` submits the whole form, carrying every dropdown's current
value in one GET request. Two things make that work correctly end to end:

1. **Redisplay** — `->urlParameterProvider(new UrlParameterProvider($this->currentRoute))`
   on the `GridView` build lets `FilterContext::value` resolve from the actual URL, so each
   dropdown redisplays whatever was actually selected, not blank, after the page reloads:

   ```php
   $dataView = (new GridView())
       ->dataReader($gridDataReader)
       ->urlCreator($urlCreator)
       ->urlParameterProvider(new UrlParameterProvider($this->currentRoute))
       ->paginationWidget($pagination)
       // ...
   ```

2. **Combining** — actual filtering is done entirely by each domain's own
   `filterCombined()` repository method, which ANDs every active filter into a single query,
   rather than by `GridView`'s own auto-filtering. `App\Widget\NoOpFilterFactory`
   (`src/Widget/NoOpFilterFactory.php`) keeps `GridView`'s auto-filtering inert — its
   `create()` always returns `Yiisoft\Data\Reader\Filter\All`, which `yiisoft/data-cycle`
   compiles to a literal `WHERE 1 = 1` — set via `filterFactory:` on every filterable column
   built from a widget object:

   ```php
   DataColumn::create(
       property: 'filterClient',
       header: ...,
       filter: (new DropdownFilter(...))->optionsData($this->filterOptions->clients),
       filterFactory: new NoOpFilterFactory(),
       withSorting: false,
       visible: !$isHidden('client'),
   ),
   ```

   `DataColumnRenderer::getFilterFactory()` only consults `defaultArrayFilterFactory` when
   `$column->filter` is a plain array, which none of these are, so this one factory override
   covers every filterable column on every grid it's applied to.

---

## Per-Grid Combining Logic

### Inv (`inv/index`)
`InvRepository::filterCombined()` ANDs every active filter (client, status, invoice number,
family name, year-month, etc.) into one query. `InvsListWidget` + `InvsColumnBuilder` (11
columns) + `InvsCategorySecondaryRunColumnTrait` (1 column) carry the redisplay/no-op-factory
wiring.

### Quote (`quote/index`)
`QuoteCombinedFilterTrait::filterCombined()` (mirrors `InvRepository::filterCombined()`)
ANDs status + `filterQuoteNumber` + `filterQuoteAmountTotal` + `filterClient` into one query,
wired into `QuoteRepository` and called from `Quote\Trait\Index::index()`.
`QuoteFilterTrait`'s individual `filterXxx()` methods are kept as-is for `quote/guest`, which
calls them independently and is out of scope here. `QuotesListWidget` +
`QuotesColumnBuilder` (4 columns: `filterStatus`, `filterQuoteNumber`, `filterClient`,
`filterQuoteAmountTotal`) carry the redisplay/no-op-factory wiring.

### Product (`product/index`)
`ProductRepository::filterCombined(array $queryParams)` ANDs `family_id` + `product_sku` +
`product_price`, and always eager-loads `family`/`tax_rate`/`unit`:

```php
public function filterCombined(array $queryParams): EntityReader
{
    $query = $this->select()->load('family')->load('tax_rate')->load('unit');
    if (!empty($queryParams['family_id'])) {
        $query = $query->andWhere(['family_id' => (int) $queryParams['family_id']]);
    }
    if (!empty($queryParams['product_sku'])) {
        $query = $query->andWhere(['product_sku' => trim((string) $queryParams['product_sku'])]);
    }
    if (!empty($queryParams['product_price'])) {
        $query = $query->andWhere(['product_price' => trim((string) $queryParams['product_price'])]);
    }
    return $this->prepareDataReader($query);
}
```

Called from `ProductController::index()`. `family_id`/`product_sku`/`product_price` are real
entity columns rather than virtual `filterXxx` names, but `NoOpFilterFactory` is applied
anyway so all real filtering stays on `filterCombined()` consistently — the default factory
for widget-based filters (`LikeFilterFactory`) would otherwise do a semantically-wrong
`LIKE` match against `family_id`. `ProductsListWidget` carries the redisplay/no-op-factory
wiring (3 columns, defined inline — no separate ColumnBuilder class for this grid).

### SalesOrder (`salesorder/index`)
One filterable column (`filterClient`) today — `SalesOrdersListWidget` +
`SalesOrdersColumnBuilder` carry the redisplay/no-op-factory wiring; nothing to combine yet.

### Not touched
`Family`, `Generator` (no filterable columns), `GatewayStatus`, and `Users` already had
`urlParameterProvider` set correctly.

---

## Files Changed

| File | Role |
|---|---|
| `src/Widget/NoOpFilterFactory.php` | Shared no-op filter factory used by all four grids |
| `src/Invoice/Inv/Widget/InvsListWidget.php`, `InvsColumnBuilder.php`, `Trait/InvsCategorySecondaryRunColumnTrait.php` | Inv redisplay + no-op-factory wiring |
| `src/Invoice/Quote/Widget/QuotesListWidget.php`, `QuotesColumnBuilder.php` | Quote redisplay + no-op-factory wiring |
| `src/Invoice/Quote/Trait/QuoteCombinedFilterTrait.php` | Quote combining logic |
| `src/Invoice/Quote/QuoteRepository.php`, `Trait/Index.php` | Wires `QuoteCombinedFilterTrait` into the index action |
| `src/Invoice/SalesOrder/Widget/SalesOrdersListWidget.php`, `SalesOrdersColumnBuilder.php` | SalesOrder redisplay + no-op-factory wiring |
| `src/Invoice/Product/Widget/ProductsListWidget.php` | Product redisplay + no-op-factory wiring |
| `src/Invoice/Product/ProductRepository.php` | Product combining logic |
| `src/Invoice/Product/ProductController.php` | Calls `filterCombined()` |

---

## Verification

Zero Psalm errors across every touched file and the full project. All four grids confirmed
working live in the browser (August 2026).
