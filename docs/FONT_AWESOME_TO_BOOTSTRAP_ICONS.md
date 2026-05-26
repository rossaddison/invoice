# FontAwesome to Bootstrap Icons Migration — Yii3-i

## Summary

FontAwesome 4/5 has been fully replaced by Bootstrap Icons (`bi bi-*`) as the sole
icon library in Yii3-i. This document records what was removed, what remains
(intentionally), and the one outstanding SCSS source item.

---

## What Was Removed

### Font files (~1.1 MB)
The entire `src/Invoice/Asset/core/fonts/` directory was deleted. It contained:

```
fontawesome-webfont.eot
fontawesome-webfont.svg
fontawesome-webfont.ttf
fontawesome-webfont.woff
fontawesome-webfont.woff2
FontAwesome.otf
```

### Orphaned CSS
`src/Invoice/Asset/core/css/all.min.css` — FA5 stylesheet with no consumers — deleted.

### Dead SCSS rules
Removed from `src/Invoice/Asset/core/scss/_core.scss`:
```scss
.fa.fa-margin { margin-right: 7px; }
.fa-credit-invoice { &:before { content: '-'; } &:after { font-family: FontAwesome; content: '\f155'; } }
.fa-read-only { @extend .fa-ban !optional; }
```

Removed from `src/Invoice/Asset/core/scss/_welcome.scss`:
```scss
.fa.fa-margin { margin-right: 7px; }
```

Removed `$fa-font-path` from `_yii3i_variables.scss` in both themes (formerly
`_ip_variables.scss`, renamed May 2026):
```scss
// deleted:
$fa-font-path: "../../core/fonts" !default;
```

### Dead rules in compiled CSS
Removed from `src/Invoice/Asset/invoice/css/style.css` (lines ~14126–14136):
```css
.fa.fa-margin { margin-right: 7px; }
.fa-credit-invoice:before { content: "-"; }
.fa-credit-invoice:after { font-family: FontAwesome; content: "\f155"; }
```

Removed from `src/Invoice/Asset/invoice/css/utilities.css` (lines ~587–594):
```css
.fa-credit-invoice:before { content: '-'; }
.fa-credit-invoice:after { font-family: FontAwesome; content: '\f155'; }
```

---

## Dark Theme SCSS Source (Resolved)

`src/Invoice/Asset/invoiceDark/sass/style.scss` previously imported FontAwesome
from node_modules:

```scss
// removed May 2026:
@import "../../../node_modules/font-awesome/scss/font-awesome";
```

This line has been removed. Recompiling the dark theme SCSS will no longer
regenerate FontAwesome rules.

---

## What Replaces FontAwesome

Bootstrap Icons is the project's icon library. It is registered via
`NodeModulesBootstrapIconsAsset` and loaded from `node_modules/bootstrap-icons/`.

Usage:
```html
<i class="bi bi-envelope"></i>
<i class="bi bi-file-earmark-pdf"></i>
<i class="bi bi-person-circle"></i>
```

Bootstrap Icons covers all icon needs previously met by FA. The full icon set is
browsable at https://icons.getbootstrap.com/.

---

## InvoicePlane Import References (Intentional)

The namespace `App\Invoice\Helpers\InvoicePlane\Exception` and translation strings
referencing "InvoicePlane" throughout `resources/messages/` are **not** FA-related.
They exist to support the data migration tool for users migrating from the legacy
InvoicePlane application and must not be altered.
