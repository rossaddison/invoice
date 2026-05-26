# SCSS Architecture — Yii3-i

## Two Independent SCSS Trees

Yii3-i maintains two parallel SCSS compilations — one for the light theme and one
for the dark theme. Each has its own entry-point file that produces a standalone
compiled CSS output.

| Theme | Entry point | Compiled output |
|-------|-------------|-----------------|
| Light | `src/Invoice/Asset/invoice/scss/style.scss` | `src/Invoice/Asset/invoice/css/style.css` |
| Dark  | `src/Invoice/Asset/invoiceDark/sass/style.scss` | `src/Invoice/Asset/invoiceDark/css/style.css` |

Each tree also has a secondary entry point for the setup/welcome screens:

| Theme | Entry point | Purpose |
|-------|-------------|---------|
| Light | `src/Invoice/Asset/invoice/scss/welcome.scss` | Setup wizard |
| Dark  | `src/Invoice/Asset/invoiceDark/sass/welcome.scss` | Setup wizard (dark) |

And standalone entry points for monospace, reports, and templates:

```
invoice/scss/monospace.scss   → invoice/css/monospace.css
invoice/scss/reports.scss     → invoice/css/reports.css
invoice/scss/templates.scss   → invoice/css/templates.css
```

---

## Import Chain (Light Theme — `style.scss`)

```
style.scss
├── _yii3i_variables.scss        ← Yii3-i status/brand colours + system font stack
├── _variables.scss              ← Bootstrap 5 variable overrides (set before Bootstrap import)
├── ../../core/scss/bootstrap    ← Full Bootstrap 5 source (compiled via node_modules)
├── includes/select2             ← Select2 SCSS
├── ../../core/scss/bootstrap-datepicker  ← Bootstrap Datepicker SCSS
├── ../../core/scss/core         ← Yii3-i layout, sidebar, component, and utility rules
└── _custom_styles.scss          ← Project-specific additions
```

The dark theme (`invoiceDark/sass/style.scss`) mirrors this chain and additionally
imports `_bootswatch.scss` for the Bootswatch dark-theme variable overrides.

---

## Variable Override Mechanism (`!default`)

Bootstrap 5 declares every variable with `!default`, meaning SCSS will use the
first value it encounters for each variable. By importing `_yii3i_variables.scss`
and `_variables.scss` **before** Bootstrap, any values set in those files silently
override Bootstrap's defaults throughout the entire compiled output — no `!important`
required.

```scss
// _yii3i_variables.scss  (imported first)
$font-family-sans-serif: -apple-system, system-ui, ... !default;

// Bootstrap picks this up and never uses its own $font-family-sans-serif
@import "../../core/scss/bootstrap";
```

If the same variable appears in both `_yii3i_variables.scss` and `_variables.scss`,
the one in `_yii3i_variables.scss` wins (imported first).

---

## File Roles

| File | Role |
|------|------|
| `_yii3i_variables.scss` | Yii3-i brand colours (`$color_status_*`), system font stack. Renamed from `_ip_variables.scss` in May 2026. |
| `_variables.scss` | Bootstrap 5 override variables (colours, spacing, breakpoints). |
| `_custom_styles.scss` | Project-level rules that sit above Bootstrap and core. |
| `core/scss/_core.scss` | Layout (sidebar, headerbar, main content area), component styles, utility classes shared across both themes. |
| `core/scss/bootstrap.scss` | Thin wrapper that `@use`s Bootstrap 5 from `node_modules/`. |
| `core/scss/bootstrap-datepicker.scss` | Bootstrap Datepicker theme. |
| `invoiceDark/sass/_bootswatch.scss` | Bootswatch Darkly variable overrides for the dark theme. |

---

## Rebuilding the Compiled CSS

SCSS is not compiled automatically on file save. After editing any `.scss` source
file, run the project's SCSS compiler (Sass CLI or the configured npm script) to
regenerate the compiled CSS output. The compiled `.css` files in
`src/Invoice/Asset/invoice/css/` and `src/Invoice/Asset/invoiceDark/css/` are
committed to the repository alongside the SCSS source.

---

## Why SCSS Rather Than Plain CSS?

| Reason | Detail |
|--------|--------|
| Bootstrap 5 integration | Bootstrap ships as SCSS; importing it as source lets variable overrides propagate through all Bootstrap components without duplication. |
| `!default` variable system | Override any Bootstrap design token in one place and have it apply everywhere the token is used. |
| Nesting | Component rules (`.sidebar`, `.headerbar`) can be written as nested blocks, matching the HTML hierarchy and reducing selector repetition. |
| Partials | The `_*.scss` prefix convention keeps supporting files out of direct compilation; each theme compiles only its own entry points. |
| `@extend` | Shared patterns (e.g. `.panel`) can be extended rather than copy-pasted. |
