# CSS Variables Reorganization — Yii3-i

## Overview

The monolithic `src/Invoice/Asset/invoice/css/style.css` (~14 000 lines, compiled
from SCSS) is being incrementally broken into six purpose-specific CSS files. The
detailed plan and import order are documented in the source directory:

[`src/Invoice/Asset/invoice/css/README.md`](../src/Invoice/Asset/invoice/css/README.md)

---

## Target Structure

| File | Purpose | Load order |
|------|---------|-----------|
| `variables.css` | CSS custom properties (design tokens) | 1st |
| `base.css` | Reset, normalize, base element styles | 2nd |
| `layout.css` | Page structure, sidebar, grid | 3rd |
| `components.css` | Buttons, forms, tables, modals | 4th |
| `utilities.css` | Single-purpose utility classes | 5th |
| `overrides.css` | Third-party and framework overrides | 6th |

All six files live in `src/Invoice/Asset/invoice/css/`.

---

## Current Status

### Live and in use
- **`variables.css`** — CSS custom properties extracted from `_yii3i_variables.scss`
  and `style.css`; covers colours, typography, spacing, borders, shadows, z-index,
  form controls, buttons, tables, grid, and component sizes.
- **`layout.css`** — main layout structure including Bootstrap 3-era mobile table
  stacking restored at `@media (max-width: 767px)`.
- **`utilities.css`** — common utility classes including `.fa-credit-invoice` (now
  cleaned of dead FontAwesome references).
- **`overrides.css`** — Bootstrap and datepicker overrides; 19 inline label style
  tags consolidated here from settings partial views.
- **`templates.css`** — PDF template-specific styles; loaded by `MpdfHelper` via
  `file_get_contents()`, not through the Yii3 asset pipeline.

### Demonstration / planning stage
`base.css`, `components.css` — contain extracted styles but are not yet wired into
any asset bundle. The original `style.css` remains the authoritative source for
all styles not yet migrated.

---

## What Still Needs Migration

Each file contains `TODO` comments pointing to un-migrated areas:

- **`variables.css`**: datepicker colours, modal dimensions, alert colours,
  progress bar colours, label/badge state colours, print media tokens
- **`base.css`**: complete typography elements, additional form input types
- **`layout.css`**: full Bootstrap 5 grid system, complete responsive utilities
- **`components.css`**: modals, navigation dropdowns, cards, full Bootstrap components
- **`utilities.css`**: complete spacing scale, additional responsive utilities
- **`overrides.css`**: Select2 overrides, additional Bootstrap overrides

---

## Migration Strategy

1. **Phase 1** (complete) — Extract design tokens into `variables.css`; demonstrate
   modular structure alongside the existing `style.css`
2. **Phase 2** (in progress) — Migrate remaining sections from `style.css` into the
   appropriate file; add `TODO` markers for anything deferred
3. **Phase 3** — Update asset bundles to load the six files instead of `style.css`
4. **Phase 4** — Delete `style.css` after thorough browser and Psalm testing

---

## Original Source Mapping

| Target file | Source location in `style.css` |
|-------------|-------------------------------|
| `variables.css` | `_yii3i_variables.scss`, scattered colour literals |
| `base.css` | Lines 1–300 (normalize section) |
| `layout.css` | Lines ~8280–8340 (layout sections) |
| `components.css` | Component sections throughout |
| `utilities.css` | Utility sections throughout |
| `overrides.css` | Lines ~7630–8061 (datepicker) + `invoiceDark/sass/_custom_styles.scss` |

---

## Dark Theme Variables

`src/Invoice/Asset/invoiceDark/sass/_yii3i_variables.scss` mirrors the light theme
file. Dark theme variable overrides are applied via `_bootswatch.scss` and
`_variables.scss` before the Bootstrap import, following the same `!default`
override mechanism described in [SCSS_ARCHITECTURE.md](SCSS_ARCHITECTURE.md).
