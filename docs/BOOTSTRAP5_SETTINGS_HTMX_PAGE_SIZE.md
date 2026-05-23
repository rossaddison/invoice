# Bootstrap 5 Settings Tabs & HTMX Page-Size Selector

## Summary

Two related improvements to the invoice layout and settings area:

1. **Bootstrap 5 compliance pass** across all settings tab partials
2. **HTMX-backed page-size selector** in the navbar — saves silently and refreshes the visible list without any page reload or redirect

---

## Bootstrap 5 Settings Tabs

### `tab_index.php`

The outer tab wrapper used Bootstrap 3-only classes (`tabbable`, `tabs-below`) and placed the `active` class on `<li>` elements (BS3 pattern). Replaced with fully compliant BS5 accessible tabs:

- `<ul>` gains `role="tablist"`
- `<li>` no longer carries `active` or `role="presentation"`
- `<button>` tabs carry `id`, `type="button"`, `role="tab"`, `aria-controls`, `aria-selected`
- Pane `<div>` carries `role="tabpanel"`, `tabindex="0"`, `aria-labelledby`

### `form-control` → `form-select` on `<select>` elements

Bootstrap 5 requires `form-select` on `<select>` elements; `form-control` only applies to `<input>` and `<textarea>`. Applied across all 16 partial files:

- Inline `H::openTag('select', [...])` calls updated directly
- Files using `$formControl = ['class' => 'form-control']` with `array_merge()`:
  - `partial_settings_taxes.php` — renamed variable to `$formSelect` (only selects used it)
  - `partial_settings_quotes.php` — kept `$formControl` for inputs/textarea; added `$formSelect` for the 6 select calls

### Label `font-weight: bold` consolidation

19 files each had an inline `echo H::tag('style', ' label { font-weight: bold; } ');` call. All 19 were removed and the rule was added once to `src/Invoice/Asset/invoice/css/overrides.css`:

```css
.card-body label { font-weight: bold; }
```

### `partial_settings_email.php` card structure

The `email_send_method` select was mistakenly rendered inside a `card-header`. Moved into its own `card-body` with an `mb-3` wrapper.

### Cron key input-group

The generate-cron-key button was not inside `input-group-text`. Fixed to the correct BS5 input-group structure:

```
div.input-group
  input.form-control        ← the cron key value
  span.input-group-text     ← wraps the button
    button.btn.btn-primary  ← generate icon (bi bi-recycle)
```

`btn-block` (BS3-only) removed from the button.

---

## HTMX Page-Size Selector

### Problem

The page-size button group in the Settings navbar dropdown called the `setting/listlimit` route, which saves the setting then redirects to `inv/index`. This caused a full-page navigation away from the current page every time the user changed their preferred list size.

### Solution

The `<a>` buttons use HTMX to save the setting silently. After the save, a small JS function re-fetches the current URL, parses the response, and swaps only `#main-area` — no redirect, no full reload.

```php
// resources/views/layout/invoice.php — array_map lambda
'<a hx-get="' . Html::encode($url) . '"'
. ' hx-swap="none"'
. ' hx-on::after-request="iPageSizeRefresh(this);"'
. ' href="' . Html::encode($url) . '"'
. ' class="btn btn-outline-secondary' . ($active ? ' active' : '') . '">'
. $size . '</a>'
```

```javascript
// iPageSizeRefresh — added as a <script> tag after NavBar::end()
function iPageSizeRefresh(btn) {
    document.querySelectorAll('#page-size-btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    fetch(window.location.href)
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const fresh = doc.getElementById('main-area');
            if (fresh) {
                document.getElementById('main-area').replaceWith(fresh);
                htmx.process(fresh);
            }
        });
}
```

### Why `fetch` + `DOMParser` rather than `htmx.ajax`

`htmx.ajax('GET', url, {target, swap, select})` was tried first. The `select` option is not reliably applied in the programmatic API — HTMX swapped the full response HTML as `outerHTML`, replacing `#main-area` with an entire `<html>` document and making the content disappear. The `fetch` + `DOMParser` + `replaceWith` approach is explicit, always works, and calls `htmx.process(fresh)` to re-activate any HTMX attributes in the newly inserted content.

### `href` fallback

The `href` attribute is kept alongside `hx-get` so the button degrades to a normal link if JavaScript is unavailable. In that case the user is navigated to `inv/index` (the original behaviour).

### Files changed

| File | Change |
|---|---|
| `resources/views/layout/invoice.php` | `hx-get` + `hx-swap="none"` + `hx-on::after-request` on page-size buttons; `iPageSizeRefresh` script tag after `NavBar::end()` |
| `resources/views/invoice/setting/tab_index.php` | BS5 tab markup |
| `resources/views/invoice/setting/views/partial_settings_general.php` | `form-select`; cron key input-group; label style removed |
| `resources/views/invoice/setting/views/partial_settings_taxes.php` | `$formSelect` variable; label style removed |
| `resources/views/invoice/setting/views/partial_settings_quotes.php` | `$formSelect` variable; label style removed |
| `resources/views/invoice/setting/views/partial_settings_email.php` | `form-select`; card structure fix; label style removed |
| 12 additional partials | `form-select` on selects; label style removed |
| `src/Invoice/Asset/invoice/css/overrides.css` | `.card-body label { font-weight: bold; }` added |
