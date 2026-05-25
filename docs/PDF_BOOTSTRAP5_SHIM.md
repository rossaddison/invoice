# PDF Bootstrap 5 Shim for mPDF

## Background

Yii3-i generates PDF documents (invoices, quotes, sales orders) via [mPDF](https://mpdf.github.io/).
mPDF's CSS engine supports a CSS 2.1 subset plus limited CSS 3. It does **not** support:

| Feature | Used by Bootstrap 5 for |
|---|---|
| CSS custom properties (`var(--bs-*)`) | colour and spacing tokens |
| Flexbox (`d-flex`, `justify-content-*`) | layout |
| CSS Grid | layout |

Loading `bootstrap.min.css` into mPDF produces nothing useful. The values resolve to empty and
layout directives are silently ignored.

The previous approach used `kartik-v/kv-mpdf-bootstrap.min.css` — a Bootstrap 3-era mPDF
compatibility shim that had stopped producing useful output.

---

## Solution

A custom shim at `src/Invoice/Asset/core/css/custom-pdf.css` maps Bootstrap 5 utility class
names to mPDF-compatible declarations. `MpdfHelper` loads this file (along with the base
`templates.css` layout) via `file_get_contents()` and injects it into every generated PDF.

No Yii3 asset bundle registration is required — mPDF reads CSS as a raw string, bypassing the
browser asset pipeline entirely.

---

## Files Changed

### `src/Invoice/Asset/core/css/custom-pdf.css` — created

A comprehensive BS5 → mPDF utility shim covering:

| Group | Classes |
|---|---|
| Text alignment | `text-start`, `text-end`, `text-center`, `text-right` (BS3 alias), `text-left` (BS3 alias) |
| Text colour | `text-primary/secondary/success/danger/warning/info/dark/light/white/muted` |
| Text decoration | `text-decoration-none/underline/line-through` |
| Font weight | `fw-bold`, `fw-bolder`, `fw-semibold`, `fw-normal`, `fw-light`, `fw-lighter` |
| Font style | `fst-italic`, `fst-normal` |
| Font size | `fs-1` … `fs-6` (30pt → 12pt) |
| Line height | `lh-1`, `lh-sm`, `lh-base`, `lh-lg` |
| Margins | `m-0` … `m-5`, `mt/mb/ms/me/mx/my-0` … `5`, `m-auto`, `ms-auto`, `me-auto`, `mx-auto` |
| Padding | `p-0` … `p-5`, `pt/pb/ps/pe/px/py-0` … `5` |
| Sizing | `w-25/50/75/100/auto`, `h-25/50/75/100/auto` |
| Display | `d-none`, `d-block`, `d-inline`, `d-inline-block`, `d-table`, `d-table-cell`, `d-flex` (→ block), `d-inline-flex` (→ inline-block) |
| Float | `float-start`, `float-end`, `float-none`, `float-left`/`float-right` (BS3 aliases) |
| Clearfix | `clearfix` → `overflow: hidden` (mPDF-safe; replaces `::after` pseudo-element) |
| Borders | `border`, `border-top/bottom/start/end`, `border-0`, `border-{colour}`, `border-1` … `5` |
| Border radius | `rounded`, `rounded-0` … `5`, `rounded-circle`, `rounded-pill` |
| Backgrounds | `bg-primary/secondary/success/danger/warning/info/light/dark/white/transparent` (light tints for print legibility) |
| Tables | `table`, `table-sm`, `table-bordered`, `table-borderless`, `table-hover`, `table-primary/secondary/success/danger/warning/info/light/dark/active`, `table-striped` (via `.odd`/`.even` TR classes) |
| Vertical alignment | `align-top`, `align-middle`, `align-bottom` |
| Overflow / visibility | `overflow-hidden`, `overflow-auto`, `visible`, `invisible` |
| Position | `position-relative`, `position-absolute`, `position-fixed` |
| Legacy custom classes | `no-margin` (→ `m-0`), `no-padding`, `no-bottom-border`, `amount`, `item-amount/price/total/discount`, `invoice-title`, `invoice-details`, `page-break`, `no-break` |

> **`table-striped` note:** mPDF does not support `:nth-child`. To activate alternating row
> shading, add `class="odd"` or `class="even"` directly to `<tr>` elements. All five PDF
> templates now do this automatically via a `$rowNum` counter in their `foreach` loops.

> **Background colours:** `bg-success`, `bg-danger`, `bg-warning` etc. use the Bootstrap 5 light
> tint variants (`#d1e7dd`, `#f8d7da`, `#fff3cd`) rather than the saturated button colours,
> for legibility on white paper.

---

### `src/Invoice/Asset/kartik-v/kv-mpdf-bootstrap.min.css` — deleted

No longer referenced anywhere. The `kartik-v/` directory was also removed.

---

### `src/Invoice/Asset/invoice/css/templates.css` — fixed

Three issues corrected:

1. **`clearfix::after` removed** — mPDF does not support `::after` pseudo-elements.
   `custom-pdf.css` defines `.clearfix { overflow: hidden }` which mPDF does support.

2. **`:nth-child(2n-1)` → `.odd`** — mPDF does not support `:nth-child` selectors.
   `table.item-table tr:nth-child(2n-1) td { background:#F5F5F5 }` replaced with
   `table.item-table tr.odd td { background:#F5F5F5 }`.

3. **`th.text-right` → `th.text-end`** — column header alignment selector updated to match
   the BS5 class name used in the templates.

---

### `src/Invoice/Helpers/MpdfHelper.php` — updated

`getCssFile()` previously loaded only `kv-mpdf-bootstrap.min.css`. It now loads both
`templates.css` (base layout and column rules) and `custom-pdf.css` (BS5 shim / overrides),
concatenated in that order:

```php
private function getCssFile(Aliases $aliases): string|false
{
    $templates = file_get_contents($aliases->get('@invoice/Asset/invoice/css/templates.css'));
    $custom    = file_get_contents($aliases->get('@invoice/Asset/core/css/custom-pdf.css'));
    if ($templates === false || $custom === false) {
        return false;
    }
    return $templates . "\n" . $custom;
}
```

The `@invoice` alias resolves to `src/Invoice` (`dirname(__DIR__)` from
`src/Invoice/Helpers/`), so both paths are filesystem references — not web-served assets.

---

### PDF templates — all five updated

Files: `template/invoice/pdf/invoice.php`, `overdue.php`, `paid.php`,
`template/quote/pdf/quote.php`, `template/salesorder/pdf/salesorder.php`

| Change | Detail |
|---|---|
| `text-right` → `text-end` | BS3 → BS5 alignment class across all `<td>` and `<th>` elements |
| `no-margin` → `m-0` | BS3 custom class → BS5 spacing utility |
| `table-primary table table-borderless` → `item-table table` | Adds `item-table` so `templates.css` column rules now apply; removes the loud blue `table-primary` header; removes `table-borderless` so the default row separators from `custom-pdf.css` render |
| `<thead style="display: none">` → `<thead>` | Column headers (Item, Description, Qty, Price, Tax, Total) are now visible — a basic requirement for professional financial documents |
| `$rowNum` / `$rowClass` counter | Added to every `foreach ($items as $item)` loop; main item `<tr>` gains `class="<?= $rowClass ?>"` (odd/even), activating the alternating row background from `templates.css` |

---

### Bug fixes in templates

**`overdue.php` — broken watermark src attribute**

```php
// Before (PHP never echoed — src was empty)
<watermarkimage src="/img/". <?php basename(__FILE__, '.php') . "png" ?> alpha="0.1">

// After
<watermarkimage src="<?= basename(__FILE__, '.php') . '.png' ?>" alpha="0.1">
```

**`quote.php` and `salesorder.php` — stray `}` inside `<table>`**

A bare `}` character on the line after the custom-fields `<tr>` was rendering as visible
text inside the PDF table. Removed from both files.

---

## Why not load Bootstrap 5 directly?

Even a stripped Bootstrap 5 bundle would pull in `var()`, `calc()`, flexbox, and grid
declarations that mPDF silently drops — leaving layout broken and file parsing slow. The shim
approach gives complete control: every rule is known to work, every colour is tuned for print,
and the file is tiny compared to the full framework.
