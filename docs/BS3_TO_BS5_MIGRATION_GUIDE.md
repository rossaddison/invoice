# Bootstrap 3 → Bootstrap 5 Migration — Difficulties and Tips
## A field guide written from experience migrating a large PHP/Yii3 application

This document records the real difficulties encountered during the incremental
BS3 → BS5 migration of Yii3-i — a ~120-view PHP application with SCSS, TypeScript,
mPDF-generated PDFs, and a server-rendered Yii3 widget layer. It is aimed at PHP
developers who are about to attempt the same.

---

## 1. The Scale Problem — Find Everything First

The single most important lesson: **grep before you touch anything.**

```bash
# Find every BS3 class still in use
grep -r "form-group\|panel-default\|panel-body\|col-xs-\|text-right\|pull-left\|pull-right\|btn-default\|label-default\|glyphicon\|navbar-toggle\|input-sm\|input-lg\|sr-only\|hidden-xs\|visible-xs\|img-responsive\|dl-horizontal\|form-inline\|table-condensed\|has-error\|has-success\|has-warning\|control-label\|help-block\|checkbox \|radio " \
  resources/views/ --include="*.php" -l | wc -l
```

In Yii3-i this returned over 120 files. Attempting the migration without that
inventory leads to partial fixes that break pages unpredictably.

**Tip:** Build a spreadsheet of class → replacement, then work file-type by file-type
(views, SCSS, compiled CSS, JavaScript, PHP widget output). Never mix the layers
in one pass — it becomes impossible to track what broke what.

---

## 2. The Class Rename Reference

### Layout and Grid

| BS3 | BS5 | Notes |
|-----|-----|-------|
| `col-xs-N` | `col-N` | xs breakpoint is now the default (no prefix) |
| `col-xs-offset-N` | `offset-N` | |
| `col-sm-offset-N` | `offset-sm-N` | Pattern: `offset-{breakpoint}-N` |
| `col-md-pull-N` | _(use order utilities)_ | `.order-{N}` |
| `col-md-push-N` | _(use order utilities)_ | |
| `hidden-xs` | `d-none d-sm-block` | One of the trickier combos |
| `visible-xs` | `d-block d-sm-none` | |
| `hidden-sm hidden-md` | `d-sm-none d-lg-block` | Combine breakpoints |
| `pull-left` | `float-start` | Left/right → start/end throughout BS5 |
| `pull-right` | `float-end` | |
| `center-block` | `d-block mx-auto` | |
| `.container-fluid` | still works | No change |

### Typography and Text

| BS3 | BS5 | Notes |
|-----|-----|-------|
| `text-left` | `text-start` | |
| `text-right` | `text-end` | This one bites PDF templates hard — see §9 |
| `text-muted` | still works | |
| `initialism` | `text-uppercase fs-6` | |
| `.page-header` | _(removed)_ | Use a `<h1>` with a bottom border or `<hr>` |
| `.lead` | still works | |

### Buttons

| BS3 | BS5 | Notes |
|-----|-----|-------|
| `btn-default` | `btn-secondary` or `btn-outline-secondary` | Most common omission |
| `btn-xs` | `btn-sm` | Extra-small removed |
| `.open > .dropdown-toggle` | _(handled automatically)_ | BS5 JS controls this |

### Forms — The Biggest Source of Churn

| BS3 | BS5 | Notes |
|-----|-----|-------|
| `form-group` | `mb-3` | 484 replacements in Yii3-i alone |
| `control-label` | `form-label` | |
| `help-block` | `form-text text-muted` | |
| `has-error` | `is-invalid` on the `<input>`, `.invalid-feedback` for message | **Applied to different element** — this is the trap |
| `has-success` | `is-valid` / `.valid-feedback` | |
| `has-warning` | `is-invalid` (closest equivalent) | |
| `checkbox` (div wrapper) | `form-check` | |
| `radio` (div wrapper) | `form-check` | |
| `form-inline` | _(removed)_ | Use `d-flex gap-2` or `row g-2` |
| `input-sm` | `form-control-sm` | |
| `input-lg` | `form-control-lg` | |
| `input-group-addon` | `input-group-text` | |
| `input-group-btn` | _(removed)_ | Put the button directly in `.input-group` |

> **Gotcha:** `has-error` was applied to the `.form-group` wrapper in BS3.
> `is-invalid` in BS5 must be applied to the `<input>` itself. PHP frameworks
> that generate validation error markup (Yii3 `ActiveForm`, Laravel `@error`)
> will emit the old pattern — you need to override the widget output or write
> a custom field renderer.

### Panels → Cards

The entire BS3 panel system is gone.

| BS3 | BS5 equivalent |
|-----|---------------|
| `panel panel-default` | `card` |
| `panel-heading` | `card-header` |
| `panel-body` | `card-body` |
| `panel-footer` | `card-footer` |
| `panel-title` | `card-title` |
| `panel panel-primary` | `card border-primary` |

> **Tip for Yii3/Yii2:** If you use `\yii\bootstrap\Panel` or similar widgets,
> those widgets still emit BS3 HTML. You must either switch to a BS5-compatible
> widget package or override the widget's `run()` method.

### Navigation and Navbar

| BS3 | BS5 |
|-----|-----|
| `navbar-default` | `navbar-light bg-light` or `navbar-dark bg-dark` |
| `navbar-fixed-top` | `fixed-top` |
| `navbar-toggle` | `navbar-toggler` |
| `navbar-header` | _(removed)_ — content moves directly into `.navbar` |
| `navbar-right` | `ms-auto` |
| `navbar-left` | `me-auto` |
| `nav-stacked` | `flex-column` |
| `.open` (dropdown state) | JS-controlled; no manual class needed |

### Labels → Badges

| BS3 | BS5 |
|-----|-----|
| `label label-default` | `badge bg-secondary` |
| `label label-primary` | `badge bg-primary` |
| `label label-success` | `badge bg-success` |
| `label label-warning` | `badge bg-warning text-dark` |
| `label label-danger` | `badge bg-danger` |
| `label label-info` | `badge bg-info text-dark` |
| `badge` (counter) | `badge rounded-pill bg-*` |

### Tables

| BS3 | BS5 |
|-----|-----|
| `table-condensed` | `table-sm` |
| `table-responsive` | still works (now a wrapper `<div>`, not a table class) |
| `.active` on `<tr>` | `table-active` |
| `.danger` on `<tr>` | `table-danger` |
| `.success` on `<tr>` | `table-success` |
| `.warning` on `<tr>` | `table-warning` |

### Miscellaneous

| BS3 | BS5 |
|-----|-----|
| `sr-only` | `visually-hidden` |
| `img-responsive` | `img-fluid` |
| `img-circle` | `rounded-circle` |
| `img-rounded` | `rounded` |
| `img-thumbnail` | still works |
| `.thumbnail` (component) | `card` |
| `.well` | `card card-body bg-light` |
| `.jumbotron` | _(removed)_ — custom card or hero section |
| `.affix` | CSS `position: sticky` |
| `.dl-horizontal` | use grid row/col |
| `breadcrumb > li` | `breadcrumb-item` class needed on `<li>` |
| `.list-inline > li` | `list-inline-item` class needed on `<li>` |

---

## 3. Data Attribute Prefix Change

Every Bootstrap JavaScript data attribute gained a `bs-` prefix.

| BS3 | BS5 |
|-----|-----|
| `data-toggle="tooltip"` | `data-bs-toggle="tooltip"` |
| `data-toggle="modal"` | `data-bs-toggle="modal"` |
| `data-toggle="collapse"` | `data-bs-toggle="collapse"` |
| `data-toggle="dropdown"` | `data-bs-toggle="dropdown"` |
| `data-dismiss="modal"` | `data-bs-dismiss="modal"` |
| `data-dismiss="alert"` | `data-bs-dismiss="alert"` |
| `data-target="#id"` | `data-bs-target="#id"` |
| `data-parent="#id"` | `data-bs-parent="#id"` |
| `data-placement="top"` | `data-bs-placement="top"` |
| `data-ride="carousel"` | `data-bs-ride="carousel"` |
| `data-spy="scroll"` | `data-bs-spy="scroll"` |
| `data-offset="N"` | `data-bs-offset="N"` |

> **PHP tip:** Yii3's `Html::tag()` and widget helpers generate `data-toggle`
> attributes if you pass `'data-toggle' => 'modal'` in the options array.
> You must change these to `'data-bs-toggle' => 'modal'` at every call site.
> A project-wide grep for `data-toggle` in `*.php` and `*.twig` will find them.

---

## 4. JavaScript API — jQuery Removed

BS5 ships with no jQuery dependency. The entire plugin API changed.

### Initialisation

```javascript
// BS3 (jQuery)
$('[data-toggle="tooltip"]').tooltip();
$('#myModal').modal('show');
$('#myCollapse').collapse('toggle');

// BS5 (vanilla JS)
const tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
tooltipEls.forEach(el => new bootstrap.Tooltip(el));

const modal = new bootstrap.Modal(document.getElementById('myModal'));
modal.show();

const collapse = bootstrap.Collapse.getOrCreateInstance('#myCollapse');
collapse.toggle();
```

### Safe initialisation pattern

If Bootstrap.js loads asynchronously or after your own scripts, guard with:

```javascript
const bs = window.bootstrap;
if (bs?.Tooltip) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => bs.Tooltip.getOrCreateInstance(el));
}
```

`getOrCreateInstance` is idempotent — call it freely without risking duplicate
tooltip instances.

### Event delegation instead of direct binding

Because PHP views are often partially refreshed (HTMX, AJAX), direct `addEventListener`
on a specific element is fragile — the element may be replaced. Use delegated listeners
on a stable ancestor:

```javascript
// Fragile
document.getElementById('btn-delete').addEventListener('click', handler);

// Robust — survives partial DOM replacement
document.addEventListener('click', event => {
    const btn = event.target.closest('#btn-delete');
    if (btn) handler(event);
}, true);  // capture phase catches events before they bubble
```

---

## 5. Modal Close Button Change

```html
<!-- BS3 -->
<button type="button" class="close" data-dismiss="modal" aria-label="Close">
    <span aria-hidden="true">&times;</span>
</button>

<!-- BS5 -->
<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
```

The `&times;` glyph is rendered by the browser via `.btn-close`'s CSS — no inner
content needed.

---

## 6. Tabs and Accordion — Accessibility Attributes

BS5 tabs require ARIA roles that BS3 did not enforce. Without them, the tab panels
do not activate correctly in some browsers.

```html
<!-- BS5 tabs — minimum required attributes -->
<ul class="nav nav-tabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active"
                id="tab-one"
                data-bs-toggle="tab"
                data-bs-target="#pane-one"
                type="button"
                role="tab"
                aria-controls="pane-one"
                aria-selected="true">Tab One</button>
    </li>
</ul>
<div class="tab-content">
    <div class="tab-pane fade show active"
         id="pane-one"
         role="tabpanel"
         aria-labelledby="tab-one">
        ...
    </div>
</div>
```

> **Hidden-tab form submission trap:** Hidden tab panes have `display: none`.
> Browser form submission skips disabled/hidden inputs. If your settings page
> has multiple tabs, inputs in inactive tabs are silently omitted from the POST.
> Fix: before `form.submit()`, temporarily set all `.tab-pane` to `display:block`
> and re-enable any disabled fields, then restore after submit.
> See `SettingsHandler.handleSettingsSubmitClick()` in `settings.ts`.

---

## 7. SCSS Override Mechanism — Critical for Bootstrap Customisation

Bootstrap 5 uses `!default` on every SCSS variable. This means: **the first
assignment wins**. Import your overrides *before* Bootstrap.

```scss
// WRONG — Bootstrap has already set $primary before this line is seen
@import "../../core/scss/bootstrap";
$primary: #2C8EDD;

// CORRECT — your value wins; Bootstrap never overrides it
$primary: #2C8EDD;
@import "../../core/scss/bootstrap";
```

In Yii3-i, `_yii3i_variables.scss` and `_variables.scss` are imported first,
so every `$variable: value !default;` in Bootstrap uses your values. This covers
colors, font stacks, spacing, border-radius, breakpoints — effectively the entire
design token system.

> **Tip:** You do not need `!important` anywhere in your custom CSS if you use
> the `!default` override system correctly. Needing `!important` in SCSS is a
> sign the import order is wrong.

---

## 8. The `input-group` / `input-group-text` Pitfall

Bootstrap 5 `input-group` works differently from BS3's `input-group-addon`.
In practice, when mixing form controls, buttons, and text add-ons inside one
`.input-group`, the border-radius calculation frequently breaks — one element gets
a rounded left edge when it should be flat, or vice versa.

**What worked better in this project:** avoid `input-group` entirely for simple cases.

```html
<!-- Avoid this pattern if layout keeps breaking -->
<div class="input-group">
    <input type="text" class="form-control">
    <span class="input-group-text">%</span>
</div>

<!-- Use small muted text instead -->
<input type="text" class="form-control">
<small class="text-muted">%</small>
```

---

## 9. mPDF and Bootstrap — They Do Not Mix

mPDF renders HTML/CSS to PDF but its CSS support is **CSS 2.1 only**:

- No CSS custom properties (`var(--bs-primary)` → silent no-op)
- No flexbox
- No CSS grid
- No `::before` / `::after` pseudo-elements
- No `:nth-child()` selector

This means you **cannot** load Bootstrap's CSS in mPDF and expect it to work.

### Solution: a purpose-built PDF shim

Create a separate CSS file (`custom-pdf.css`) that gives BS5 class names
mPDF-compatible backing declarations using only properties mPDF understands:
float, margin, padding, border, color, background, text-align, font-*, width, display.

Key mapping examples:

```css
/* text utilities */
.text-end   { text-align: right; }
.text-start { text-align: left; }
.text-right { text-align: right; } /* keep BS3 alias — PDF templates often haven't been updated */

/* spacing */
.m-0  { margin: 0; }
.mb-3 { margin-bottom: 15px; }
.p-2  { padding: 8px; }

/* tables — no :nth-child in mPDF */
/* table-striped does nothing; use .odd/.even TR classes instead */
table.item-table tr.odd td { background: #F5F5F5; }

/* clearfix — ::after not supported */
.clearfix { overflow: hidden; }  /* overflow: hidden clears floats */

/* display */
.d-none  { display: none; }
.d-block { display: block; }
/* No flexbox — d-flex does nothing */
```

Load it via `file_get_contents()` directly, not through the asset pipeline:

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

### Striped table rows in mPDF

```php
// In your PDF template foreach loop:
$rowNum = 0;
/**
 * @var MyEntity $item
 */
foreach ($items as $item) {
    $rowNum++;
    $rowClass = ($rowNum % 2 === 1) ? 'odd' : 'even';
    // ...
    echo '<tr class="' . $rowClass . '">';
}
```

> **Psalm warning:** If you place `$rowNum = 0;` between the `@var` docblock and
> the `foreach`, Psalm loses its type annotation and emits `[MixedAssignment]`.
> Always put the counter *before* the `@var` docblock.

---

## 10. FontAwesome Removal

BS3 era projects often depend on FontAwesome for icons. Removing it requires a
systematic approach — it is easy to miss instances embedded in:

- PHP widgets that build HTML strings (`'<i class="fa fa-trash"></i>'`)
- TypeScript/JavaScript `innerHTML` assignments
- SCSS `content:` properties referencing FA Unicode values
- PHP controller comments referencing jQuery/FA patterns

**Checklist:**

```bash
# Find all FA references
grep -r "FontAwesome\|font-awesome\|fa-font-path\|fa fa-\|class=\"fa \|fa-spinner\|fa-check\|fa-times" \
  src/ resources/ --include="*.php" --include="*.ts" --include="*.js" --include="*.scss" --include="*.css" -l

# Check for fa-spin (CSS animation class) separately — it may survive as a class
# name in JS even after FA CSS is removed
grep -r "fa-spin" src/ resources/ -l
```

**Replacement map:**

| FA pattern | Bootstrap 5 equivalent |
|-----------|------------------------|
| `<i class="fa fa-spin fa-spinner">` | `<span class="spinner-border spinner-border-sm" role="status"></span>` |
| `<i class="fa fa-check">` | `<i class="bi bi-check-lg">` |
| `<i class="fa fa-times">` | `<i class="bi bi-x-lg">` |
| `<i class="fa fa-trash">` | `<i class="bi bi-trash">` |
| `<i class="fa fa-edit">` | `<i class="bi bi-pencil">` |
| `<i class="fa fa-plus">` | `<i class="bi bi-plus-lg">` |
| `fa-spin` (CSS animation) | Custom `@keyframes icon-spin` — BS5 has no built-in spin class |

**The `fa-spin` trap:** When you remove FontAwesome, the `.fa-spin` CSS class
vanishes with it. Any JS that calls `element.classList.add('fa-spin')` silently
stops working. Add your own animation class to `overrides.css`:

```css
@keyframes icon-spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}
.icon-spin { animation: icon-spin 1s linear infinite; }
```

---

## 11. SonarCloud / SonarQube Duplicate Selector Warnings

After the migration, SonarCloud may flag duplicate CSS selectors across your
compiled `style.css`. This is because the file was compiled from SCSS that
included Bootstrap — the compiled output legitimately repeats selectors across
components.

**Solution:** Exclude your compiled asset directory from SonarCloud analysis:

```properties
# sonar-project.properties
sonar.exclusions=src/Invoice/Asset/**
```

This removes thousands of false-positive duplication warnings without hiding any
application code issues.

---

## 12. Yii3 / Yii2 Framework-Specific Issues

### GridView and DetailView

Yii's `GridView` emits BS3 classes by default (`table-condensed`, pager using
`pagination` with `<li>` items that lack `page-item`/`page-link` classes).
Options:

1. Override the widget via `tableOptions`, `rowOptions`, `pagerOptions` on each
   `GridView::widget()` call
2. Extend `GridView` in your own widget class and override `renderTableBody`,
   `renderPager`, etc.
3. Use a BS5-compatible GridView package

### ActiveForm

Yii3/Yii2 `ActiveForm` and field renderers emit `form-group`, `has-error`,
`control-label`, `help-block`. You need a custom `ActiveField` class that outputs
the BS5 equivalents, or override the field's `template` and individual methods.

### Widget icon output

Any Yii widget that builds HTML with FA classes (toolbar buttons, action columns,
etc.) must be updated at the PHP layer — the icon string is built in PHP, not
in a template. Search for `'icon' =>` and `fa-` in widget classes.

---

## 13. The "Mobile Table Stacking" Regression

BS3 shipped `.table-responsive` as a full-width scroll wrapper. Many projects
also relied on a media query pattern that stacked table cells vertically on
mobile:

```css
@media (max-width: 767px) {
    table, thead, tbody, th, td, tr { display: block; }
}
```

When you remove Bootstrap 3's CSS, this media query goes with it. BS5 does not
include it — its table-responsive is horizontal scroll only. You need to add
the stacking rules back explicitly, scoped to prevent breaking `GridView`'s
`data-label`-less cells:

```css
@media (max-width: 767px) {
    table.responsive-stack,
    table.responsive-stack thead,
    table.responsive-stack tbody,
    table.responsive-stack th,
    table.responsive-stack td,
    table.responsive-stack tr { display: block; }

    table.responsive-stack thead tr { display: none; }

    table.responsive-stack td[data-label]::before {
        content: attr(data-label);
        font-weight: bold;
        display: block;
    }

    table.responsive-stack td {
        border: none;
        padding-left: 50%;
        position: relative;
    }
}
```

---

## 14. Tooltip Initialisation Order

Bootstrap tooltips require `window.bootstrap` to be defined when your
initialisation code runs. In an asset-bundled application, the load order
of scripts matters.

**Symptom:** `bootstrap is not defined` or tooltips silently not working.

**Cause:** Your IIFE runs before `bootstrap.bundle.js` has executed.

**Fix:** Ensure `BootstrapJsOnlyAsset` (or equivalent) is registered before
the asset bundle that runs your tooltip init. In Yii3:

```php
// In your view or layout
$this->registerAssetBundle(BootstrapJsOnlyAsset::class);
$this->registerAssetBundle(InvoiceAsset::class);
```

Also use `Tooltip.getOrCreateInstance()` rather than `new Tooltip()` — it is
idempotent and safe to call multiple times on the same element (e.g. after an
HTMX partial swap).

---

## 15. Incremental Migration Strategy

Attempting a full cutover in one PR is high-risk in a large PHP application.
What worked in Yii3-i:

1. **Run BS3 and BS5 side by side** — keep Bootstrap 3 SCSS compiled output
   in place; load BS5 alongside it. Identify conflicts, fix them, then remove BS3.

2. **Start with the easiest wins** — `form-group → mb-3` (mechanical, grep-safe),
   `data-toggle → data-bs-toggle` (grep-safe), `btn-default → btn-secondary` (grep-safe).

3. **Leave the panel system last** — `panel → card` requires structural HTML changes
   (not just class renames) and breaks more things when done at scale.

4. **Exclude from static analysis during transition** — SonarCloud/SonarQube will
   flag thousands of issues in partially-migrated files. Either raise the threshold
   temporarily or exclude the asset directory until the migration is complete.

5. **Test PDF output separately** — browser rendering and mPDF rendering are
   completely independent. A class that works fine in the browser may silently
   produce nothing in mPDF. Always generate a test PDF after changing PDF template classes.

6. **Use branch protection** — do the migration on a dedicated branch. The diff will
   be large; reviewing it as a single PR makes regressions easier to spot.

---

## 16. Quick-Reference Grep Patterns

```bash
# BS3 classes still present in PHP views
grep -rn "form-group\|panel-\|col-xs-\|btn-default\|text-right\|pull-left\|pull-right\|has-error\|has-success\|input-sm\|sr-only\|label-default\|table-condensed" \
  resources/views/ --include="*.php"

# Old data attributes
grep -rn "data-toggle\|data-dismiss\|data-target\|data-parent" \
  resources/views/ --include="*.php"

# FontAwesome survivors
grep -rn "fa fa-\|class=\"fa \|font-awesome\|FontAwesome\|fa-spin" \
  src/ resources/ --include="*.php" --include="*.ts" --include="*.js" --include="*.scss" --include="*.css"

# jQuery survivors (comments are OK, actual usage is not)
grep -rn "\$\('" src/ resources/ --include="*.js" --include="*.ts" --include="*.php"

# BS3 SCSS variables or mixins
grep -rn "@include make-xs-column\|@include make-sm-column\|mixin grid-float-breakpoint\|\$screen-xs" \
  src/ --include="*.scss"
```

---

## Summary — The Ten Things That Surprised Claude Most

1. **`has-error` moves to the input, not the wrapper** — the most semantically significant change in the form system.
2. **All `data-toggle` attributes need `data-bs-` prefix** — easy to forget in PHP-generated attributes.
3. **mPDF ignores CSS custom properties entirely** — every `var(--bs-*)` reference is a silent no-op.
4. **`:nth-child` doesn't work in mPDF** — `table-striped` produces nothing; you need `.odd`/`.even` TR classes.
5. **`fa-spin` is a CSS class, not just a font class** — removing FA removes the animation; you need your own `@keyframes`.
6. **Hidden tab inputs are omitted from form POST** — the settings multi-tab form silently drops data from inactive panes.
7. **SCSS `!default` means first-wins** — your overrides must come before the Bootstrap import, not after.
8. **Tooltip double-init causes errors** — always use `getOrCreateInstance()`, especially after HTMX partial swaps.
9. **`input-group` border-radius arithmetic breaks easily** — simpler markup (plain text or small) is more reliable.
10. **The Yii widget layer emits BS3 HTML** — renaming classes in views is not enough if the widget still renders `form-group` and `control-label` in PHP.
