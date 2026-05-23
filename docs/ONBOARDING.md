# Bootstrap 5 Table Mobile Stacking Fix

## What changed

Added table stacking rules to `src/Invoice/Asset/invoice/css/layout.css` inside
the `@media (max-width: 767px)` block.  This restores the mobile behaviour that
existed on `main` branch before Bootstrap 3 CSS was stripped.

## Why it was missing

`main/style.css` line 8729 had Bootstrap 3-era rules making every `<table>`,
`<tr>`, `<td>`, `<th>` `display: block` at narrow widths.  When Bootstrap 3
CSS was removed on the `removebootstrap3css` branch, those rules went with it.
Bootstrap 5 does not have an equivalent stacking rule — it uses a
`.table-responsive` wrapper div for horizontal scroll instead.

## The fix (layout.css)

```css
@media (max-width: 767px) {
  table, thead, tbody, th, td, tr { display: block; }

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
}
```

`td[data-label]` scoping avoids a 50 % left-padding gap on Yiisoft GridView
cells that do not emit `data-label` attributes.

## Full write-up

See [docs/BOOTSTRAP5_TABLE_MOBILE_STACKING.md](docs/BOOTSTRAP5_TABLE_MOBILE_STACKING.md).
