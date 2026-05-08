# Sidebar Improvements

## Overview

The invoice layout sidebar (`resources/views/invoice/layout/sidebar.php`) was refactored from seven repeated `<li><a><i>` blocks into a data-driven `$items` array with a single `foreach` loop. At the same time, all Font Awesome 4 icons were replaced with Bootstrap Icons, active-state detection was added, per-item accent colours were introduced via a CSS custom property, and a dark-navy gradient background with hover/active transitions was applied.

---

## 1. Font Awesome → Bootstrap Icons Migration

The sidebar previously mixed two icon libraries. Five items still used Font Awesome 4 (`fa fa-*`) while two already used Bootstrap Icons (`bi bi-*`). All icons were consolidated to Bootstrap Icons, which is already loaded as part of the application asset bundle.

| Item | Before | After |
|---|---|---|
| Quotes | `fa fa-file` | `bi bi-chat-square-text` |
| Invoices | `fa fa-file-text` | `bi bi-file-text` |
| Products | `fa fa-database` | `bi bi-box-seam` |
| Tasks | `fa fa-check-square-o` | `bi bi-check2-square` |
| Settings | `fa fa-cogs` | `bi bi-gear` |
| Clients | `bi bi-people` | *(unchanged)* |
| Payments | `bi bi-coin` | *(unchanged)* |

---

## 2. Array + `foreach` Refactor

Seven sets of manually repeated `H::openTag('li') ... H::openTag('a') ... H::openTag('i')` blocks — including a standalone `if ($s->getSetting('projects_enabled') == 1)` conditional — were replaced with a single `$items` array and one `foreach` loop.

Each entry declares five fields:

```php
$items = [
    ['route' => 'client/index',  'title' => 'clients',  'icon' => 'bi bi-people',   'color' => '#0d6efd', 'show' => true],
    ['route' => 'task/index',    'title' => 'tasks',    'icon' => 'bi bi-check2-square', 'color' => '#0dcaf0',
     'show' => $s->getSetting('projects_enabled') == 1],
    // ...
];
```

The `'show'` field replaces the conditional `if` block — items with `'show' => false` are skipped via `continue` in the loop.

---

## 3. Active-State Detection

`$currentRoute` was added to the `@var` block and used to detect which controller is currently active:

```php
$currentName = $currentRoute->getName() ?? '';
$prefix      = explode('/', $item['route'])[0];   // e.g. 'client' from 'client/index'
$isActive    = str_starts_with($currentName, $prefix);
```

The `active` CSS class is then added conditionally:

```php
'class' => 'tip' . ($isActive ? ' active' : ''),
```

This means navigating to any route under `client/*` highlights the Clients sidebar icon, and so on for all items.

---

## 4. Per-Item Colour via CSS Custom Property

Each `<a>` element receives a `--sidebar-color` CSS variable inline:

```php
'style' => '--sidebar-color: ' . $item['color'],
```

The shared `<style>` block consumes this variable for both the hover border and the active icon colour:

```css
.sidebar li a:hover  { border-left-color: var(--sidebar-color); }
.sidebar li a.active { border-left-color: var(--sidebar-color); color: var(--sidebar-color); }
```

This means one CSS rule-set drives all seven accent colours without a separate rule per item.

### Colour Assignments

| Item | Colour |
|---|---|
| Clients | `#0d6efd` (blue) |
| Quotes | `#198754` (green) |
| Invoices | `#198754` (green) |
| Payments | `#fd7e14` (orange) |
| Products | `#6f42c1` (purple) |
| Tasks | `#0dcaf0` (teal) |
| Settings | `#6c757d` (gray) |

These match the accent palette used in the settings tabs for visual consistency.

---

## 5. Dark Gradient Background and Transitions

The sidebar background and link states are styled via an injected `<style>` block:

```css
.sidebar {
    background: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
}
.sidebar li a {
    display: flex; align-items: center; justify-content: center;
    padding: 12px 0;
    color: rgba(255,255,255,0.65);
    text-decoration: none;
    transition: background 0.15s, color 0.15s, border-left-color 0.15s;
    border-left: 3px solid transparent;
}
.sidebar li a:hover {
    background: rgba(255,255,255,0.08);
    color: #fff;
    border-left-color: var(--sidebar-color);
}
.sidebar li a.active {
    border-left-color: var(--sidebar-color);
    color: var(--sidebar-color);
    background: rgba(255,255,255,0.06);
}
.sidebar li a i { font-size: 1.4em; }
```

The gradient matches the navbar and footer in `main.php`, creating a consistent dark-navy frame across the entire application shell. The `3px solid transparent` border-left on every link reserves the space so items don't shift horizontally when the border becomes visible on hover or active.

---

## File Modified

`resources/views/invoice/layout/sidebar.php`

---

## 6. Sidebar Background Colour Setting

The dark-navy gradient background that was originally hardcoded in the `<style>` block was replaced with a user-configurable setting stored in the database and controlled from the Bootstrap5 settings panel.

### New Setting Keys

| Setting Key | Default | Applies To |
|---|---|---|
| `bootstrap5_sidebar_background` | `#1a1a2e` | Authenticated user sidebar (`sidebar.php`) |
| `bootstrap5_sidebar_guest_background` | `#1a1a2e` | Guest sidebar (`sidebar_guest.php`) |

### New Files

**`resources/views/invoice/setting/views/bootstrap5/partial_sidebar.php`**

Renders two `<input type="color">` pickers side by side inside the Bootstrap5 settings panel — one for the main sidebar, one for the guest sidebar. Follows the same `border-secondary` border convention as other bootstrap5 partials.

### Changes to Existing Files

| File | Change |
|---|---|
| `src/Invoice/Setting/Trait/SettingsTabBootstrap5.php` | Added `bootstrap5_sidebar_background` and `bootstrap5_sidebar_guest_background` to `buildBootstrap5Body()`; added `$sidebar` partial render; added `$sep . $sidebar` to the assembly string |
| `src/Invoice/InvoiceController.php` | Added `'bootstrap5_sidebar_background' => '#1a1a2e'` and `'bootstrap5_sidebar_guest_background' => '#1a1a2e'` to the default settings array |
| `resources/views/invoice/layout/sidebar.php` | Replaced the hardcoded `background: linear-gradient(...)` with `background: <?= $s->getSetting('bootstrap5_sidebar_background') ?: '#1a1a2e'; ?>` |
| `resources/views/invoice/layout/sidebar_guest.php` | Same replacement using `bootstrap5_sidebar_guest_background` |

### How It Works

The colour picker in the settings panel posts to the standard settings save route. On next page load `$s->getSetting('bootstrap5_sidebar_background')` returns the saved hex value, which is written directly into the inline `background` style on the `.sidebar` element. The `?: '#1a1a2e'` fallback ensures the original dark-navy colour is preserved if the setting has never been saved.
