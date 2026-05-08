# Settings Tabs Improvements

## Overview

The settings page (`resources/views/invoice/setting/tab_index.php`) was fully rewritten from inline HTML to the `Yiisoft\Html\Html as H` convention and enhanced with icon-based navigation, per-tab accent colours, font inheritance, and bold labels across all 20 partials.

---

## 1. H:: Convention Rewrite

All raw HTML was replaced with `H::openTag()` / `H::closeTag()` / `H::tag()` calls, consistent with the rest of the invoice view layer. A `$tabs` keyed PHP array drives two `foreach` loops — one for the `<ul>` nav items and one for the `.tab-content` panes — eliminating 21 repeated blocks of manually written HTML.

### `$tabs` Array Structure

The array is keyed by the Bootstrap tab slug (e.g. `'front-page'`). Each entry contains:

```php
$tabs = [
    'front-page' => [
        'label'   => $translator->translate('settings'),
        'icon'    => 'bi bi-house',
        'color'   => '#0d6efd',
        'aria'    => 'front_page_tab',
        'role'    => 'front_page',
        'content' => $this->render('views/partial_settings_front_page', ...),
    ],
    // ... 20 more entries
];
```

### Nav Loop

```php
foreach ($tabs as $key => $tab) {
    echo H::openTag('li', ['class' => 'nav-item', 'role' => 'presentation']);
     echo H::openTag('button', [
         'class'          => 'nav-link' . ($key === 'front-page' ? ' active' : ''),
         'id'             => $tab['aria'],
         'data-bs-toggle' => 'tab',
         'data-bs-target' => '#' . $tab['role'],
         'role'           => 'tab',
         'style'          => '--tab-color:' . $tab['color'] . '; font:inherit; text-decoration:none',
     ]);
      echo H::tag('i', '', ['class' => $tab['icon']]);
      echo H::tag('span', $tab['label']);
     echo H::closeTag('button');
    echo H::closeTag('li');
}
```

### Pane Loop

```php
foreach ($tabs as $key => $tab) {
    echo H::openTag('div', [
        'class' => 'tab-pane fade' . ($key === 'front-page' ? ' show active' : ''),
        'id'    => $tab['role'],
        'role'  => 'tabpanel',
    ]);
     echo $tab['content'];
    echo H::closeTag('div');
}
```

---

## 2. Tab Icons (Bootstrap Icons)

Each tab entry has an `'icon'` field containing a Bootstrap Icons CSS class. The icon is rendered as `<i class="...">` inside the button, above the label text. Bootstrap Icons is already loaded as part of the application asset bundle.

### Color Groups

| Color | Hex | Tabs |
|---|---|---|
| Blue | `#0d6efd` | Front Page, OAuth2, General, 2FA |
| Green | `#198754` | Invoices, Quotes, Client Purchase Orders |
| Orange | `#fd7e14` | Taxes, VAT Registered, Making Tax Digital |
| Purple | `#6f42c1` | Email, Online Payment, Telegram |
| Teal | `#0dcaf0` | Peppol, Storecove, InvoicePlane, QR Code |
| Gray | `#6c757d` | Projects & Tasks, Google Translate, mPDF, Bootstrap5 |

---

## 3. Stacked Icon Layout and CSS Custom Property

The CSS custom property `--tab-color` is set inline on each `<button>` so one shared `<style>` block drives all 21 accent colours without separate rules per tab.

```css
#settings-tabs .nav-link {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 6px 10px;
    border-top: 3px solid var(--tab-color, transparent);
    border-radius: 4px 4px 0 0;
}
#settings-tabs .nav-link i         { font-size: 1.25em; color: var(--tab-color); }
#settings-tabs .nav-link.active i  { color: #fff; }
#settings-tabs .nav-link:hover     { background: rgba(0,0,0,0.05); }
#settings-tabs .nav-link.active    { background: var(--tab-color); color: #fff; }
```

The icon sits above the label text (`flex-direction: column`) and receives the tab's own accent colour when inactive, turning white on the active tab.

---

## 4. Font Inheritance

A `$font` and `$fontSize` variable wrap the outer container `<div>` in an inline `style` attribute. Bootstrap and browser defaults prevent form elements from inheriting fonts. The injected `<style>` block overrides this:

```css
h1, h2, h3, h4, h5, h6,
select, input, textarea, button,
.form-control,
.panel-heading, .panel-body, .panel-title, .panel-footer {
    font: inherit;
}
.panel-heading            { font-weight: bold; }
.panel-heading h6         { font-weight: bold; }
```

This ensures that changing `$font` or `$fontSize` in the wrapping div cascades into every form control, panel, and heading inside settings — including partials rendered via `$this->render()`.

---

## 5. Bold Labels Across All 20 Partials

Each of the 20 partial view files (`partial_settings_general.php`, `partial_settings_email.php`, etc.) has the following inserted as the very first output line:

```php
echo H::tag('style', ' label { font-weight: bold; } ');
```

This scoped `<style>` block targets only labels within that partial's rendered output. It was applied to all 20 partials in a single batch operation.

---

## 6. Global Form Font Size and Input Height Settings

All per-form font-size and input-height wiring (originally client-form-only) was generalised to a single pair of Bootstrap5 settings that apply to **every** `_form.php` view in the package via CSS custom properties in the layout `<head>`.

### New Setting Keys

| Setting Key | Default | Effect |
|---|---|---|
| `bootstrap5_form_font_size` | `14` | Font size (px) for all `.container-fluid` form wrappers |
| `bootstrap5_form_input_height` | `38` | Minimum height (px) for all `input`, `.form-control`, `.form-select` elements |

### New Partial

**`resources/views/invoice/setting/views/bootstrap5/partial_forms.php`**

Renders two `<select>` pickers side by side — font size (5–20 px) and input height (32–60 px, step 2) — inside the Bootstrap5 settings panel as a new `$sep`-delimited section.

### CSS Custom Properties in `invoice.php` Layout

Values are read by `LayoutViewInjection` and written into `:root` as:

```css
:root {
    --inv-form-fs:     14px;   /* from bootstrap5_form_font_size   */
    --inv-input-height: 38px;  /* from bootstrap5_form_input_height */
}
```

Three rules then consume these variables globally (with `!important` to override Bootstrap's later-loaded stylesheet):

```css
input, .form-control, .form-select {
    min-height: var(--inv-input-height) !important;
}
.container-fluid {
    font-size: var(--inv-form-fs) !important;
}
.container-fluid input, .container-fluid select, .container-fluid textarea,
.container-fluid button, .container-fluid .form-control, .container-fluid .form-select,
.container-fluid .form-check-label, .container-fluid label {
    font-size: inherit !important;
    font-family: inherit;
}
.container-fluid .card-header { font-weight: bold; }
```

All 40+ `_form.php` views benefit automatically — no per-controller or per-view changes needed.

### `LayoutViewInjection` Changes

`src/ViewInjection/LayoutViewInjection.php` reads both settings and returns them as `$bootstrap5FormFontSize` (int) and `$bootstrap5FormInputHeight` (int) in `getLayoutParameters()`. These are then available as `$bootstrap5FormFontSize` and `$bootstrap5FormInputHeight` in the layout view.

### Info Views Updated

Nine views in `resources/views/invoice/info/` previously used an `$fontSize` variable passed from the controller via an old per-view path. They now use the global CSS variable directly — eliminating the undefined-variable error:

```html
<!-- Before -->
<div style="font-size: <?= $fontSize + 2 ?: 10; ?>px;">

<!-- After -->
<div style="font-size: calc(var(--inv-form-fs) + 2px);">
```

Php-concatenation variants in `console_commands.php` and `codeception_selectors_checklist.php` were updated to use `var(--inv-form-fs)` in `->addStyle()` calls.

### Files Modified / Created

| File | Change |
|---|---|
| `resources/views/invoice/setting/views/bootstrap5/partial_forms.php` | **Created** — font size + input height selects |
| `src/Invoice/Setting/Trait/SettingsTabBootstrap5.php` | New body keys; `$forms` partial render; added to assembly |
| `src/Invoice/InvoiceController.php` | Defaults for both new keys |
| `src/ViewInjection/LayoutViewInjection.php` | Reads both settings; returns as typed int parameters |
| `resources/views/layout/invoice.php` | `:root` CSS vars + global form rules with `!important` |
| `resources/views/invoice/info/*.php` (9 files) | Removed `$fontSize` usage; replaced with `calc(var(--inv-form-fs) + 2px)` |

---

## 5. Bold Labels Across All 20 Partials

Each of the 20 partial view files (`partial_settings_general.php`, `partial_settings_email.php`, etc.) has the following inserted as the very first output line:

```php
echo H::tag('style', ' label { font-weight: bold; } ');
```

This scoped `<style>` block targets only labels within that partial's rendered output. It was applied to all 20 partials in a single batch operation.

---

## Files Modified

| File | Change |
|---|---|
| `resources/views/invoice/setting/tab_index.php` | Full rewrite to H:: convention with `$tabs` array, icons, colors, CSS |
| `resources/views/invoice/setting/views/partial_settings_general.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_invoices.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_email.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_taxes.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_online_payment.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_vat_registered.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_quotes.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_peppol.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_storecove.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_invoiceplane.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_qr_code.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_making_tax_digital.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_telegram.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_oauth2.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_two_factor_authentication.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_mpdf.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_google_translate.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_projects_tasks.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_client_purchase_orders.php` | Bold label style added |
| `resources/views/invoice/setting/views/partial_settings_front_page.php` | Bold label style added |
| `resources/views/invoice/setting/views/bootstrap5/partial_forms.php` | **Created** — global form font size and input height selects |
| `src/Invoice/Setting/Trait/SettingsTabBootstrap5.php` | New body keys; `$forms` partial render and assembly |
| `src/Invoice/InvoiceController.php` | Default values for `bootstrap5_form_font_size` and `bootstrap5_form_input_height` |
| `src/ViewInjection/LayoutViewInjection.php` | Reads both settings; exposes as `$bootstrap5FormFontSize` and `$bootstrap5FormInputHeight` |
| `resources/views/layout/invoice.php` | `:root` variables `--inv-form-fs` and `--inv-input-height`; global form rules with `!important` |
| `resources/messages/en/app.php` | Added `bootstrap5.form.font.size` and `bootstrap5.form.input.height` translation keys |
| `resources/views/invoice/info/*.php` (9 files) | Removed `$fontSize` dependency; replaced with `calc(var(--inv-form-fs) + 2px)` |
