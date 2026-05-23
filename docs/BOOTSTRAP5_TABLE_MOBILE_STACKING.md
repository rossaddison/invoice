# Bootstrap 5 — Table Mobile Stacking Fix

## Key Finding

When Bootstrap 3 CSS is stripped, the `display: block` table stacking rules go
with it — Bootstrap 5 does not replace them, so they must be explicitly ported
into `layout.css`.

## Problem

On the `removebootstrap3css` branch, the mobile preview (390 px popup) showed
invoice/quote/payment tables as wide, non-responsive grids — cells did not stack
vertically at narrow widths.

## Root Cause

`main` branch `style.css` contained this Bootstrap 3-era rule at line 8729:

```css
@media (max-width: 767px) {
    table, thead, tbody, th, td, tr {
        display: block;
    }
    td {
        position: relative;
        padding-left: 50%;
    }
    td:before {
        position: absolute;
        left: 10px;
        content: attr(data-label);
    }
}
```

When Bootstrap 3 CSS was stripped from `style.css` on `removebootstrap3css`,
these rules were removed.  Bootstrap 5 has no equivalent — it only provides
horizontal scroll via a `.table-responsive` wrapper `<div>`, which is a
different pattern.

## Fix

Ported the stacking rules into `src/Invoice/Asset/invoice/css/layout.css`
inside the existing `@media (max-width: 767px)` block.  The `data-label`
label-column rules are scoped to `td[data-label]` rather than all `td` to
avoid a 50 % left-padding gap on GridView cells that do not emit `data-label`
attributes.

```css
/* layout.css — @media (max-width: 767px) */
table, thead, tbody, th, td, tr {
  display: block;
}

td[data-label] {
  position: relative;
  padding-left: 50%;
}

td[data-label]:before {
  position: absolute;
  left: 10px;
  content: attr(data-label);
  font-weight: bold;
}
```

## Behaviour after fix

| Viewport | Behaviour |
|----------|-----------|
| ≥ 768 px | Normal table layout — rows horizontal, columns side-by-side |
| < 768 px | Each `<tr>` becomes a full-width block; each `<th>`/`<td>` stacks vertically |

## Column labels (data-label)

The `td:before` rule reads the `data-label` attribute to show a column name
beside each stacked cell — a common progressive-enhancement pattern for
responsive tables.  The Yiisoft GridView widget (`InvsListWidget`,
`QuotesListWidget`, etc.) does **not** currently emit `data-label` attributes,
so labels are blank.  To enable them, add `data-label` to each `<td>` via the
column renderer's `bodyCellAttributes` callback:

```php
->bodyCellAttributes(static function (DataContext $ctx): array {
    return ['data-label' => $ctx->column->getLabel()];
})
```

## Side-effects to watch

The `display: block` rule applies globally to **all** tables at < 768 px.
Pages that need table-row layout preserved at mobile widths (e.g. a specific
settings form) must override locally:

```css
@media (max-width: 767px) {
  .my-layout-table tr  { display: table-row; }
  .my-layout-table td  { display: table-cell; }
}
```

The existing `tfoot tr { display: flex }` in `inv/index.php` inline CSS
overrides correctly because `tfoot tr` is more specific than `tr` and the
inline `<style>` loads after `layout.css`.
