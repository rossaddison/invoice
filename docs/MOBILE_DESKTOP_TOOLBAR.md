# Mobile / Desktop Preview Toolbar

## Overview

The **Mobile Preview Toggle** is a floating toolbar button injected into the invoice guest list view (`resources/views/invoice/inv/guest.php`) that lets users instantly preview the current page inside a simulated Android phone frame — without leaving the page or opening browser developer tools.

It is implemented as a self-contained `MobilePreviewToggle` class written inline in the view's `$mobilePreviewScript` block and is output as a standard `<script type="module">`.

---

## How It Works

### Activation

A fixed-position button labelled **📱 Mobile Preview** is appended to `document.body` after the DOM is ready. Clicking it opens a full-screen overlay containing an iframe that reloads the current URL at **390 px width** — the standard Android viewport.

### Deactivation

Clicking the button again (now labelled **🖥️ Desktop View**) or pressing **Escape** closes the overlay and restores the normal desktop view.

### Self-iframe guard

The class checks `window.self !== window.top` at construction time. If the script is running inside its own preview iframe it exits immediately, preventing an infinite nesting loop.

---

## DOM Structure

When activated the following elements are injected into `document.body`:

```
#mp-overlay                        Full-screen dark gradient backdrop
└── #mp-phone                      390 px phone bezel (border-radius + box-shadow)
    ├── #mp-badge                  "📱 Android — 390 × 844 px" label above phone
    ├── #mp-notch-bar              Top speaker/notch strip
    │   └── #mp-notch              Centred pill notch
    ├── #mp-iframe                 390 × 800 px iframe (src = window.location.href)
    └── #mp-home-bar               Bottom home-indicator bar
        └── #mp-home-ind           Pill indicator
#mp-hint                           "Press Esc or click 🖥️ Desktop View to exit" hint
```

The overlay element is only built once (`buildOverlay()` skips if `#mp-overlay` already exists) and is then shown/hidden by toggling the CSS class `mp-show`.

---

## CSS Classes

All styles are injected into `<head>` via a `<style id="mp-styles">` element. The styles are injected only once — if `#mp-styles` already exists the injection is skipped.

| Selector | Description |
|---|---|
| `.mp-btn` | The fixed toggle button (bottom-right corner, `z-index: 10001`) |
| `.mp-btn.mp-on` | Active state — button turns Bootstrap blue (`#0d6efd`) |
| `#mp-overlay` | Full-screen darkened overlay (`z-index: 10000`) |
| `#mp-overlay.mp-show` | Makes overlay visible (`display: flex`) |
| `#mp-phone` | Phone frame — 390 px wide, rounded bezel with layered `box-shadow` |
| `#mp-badge` | Small floating label positioned above the phone frame |
| `#mp-notch-bar` / `#mp-notch` | Simulated top notch / speaker area |
| `#mp-iframe` | `390 × 800 px`, `border: none` |
| `#mp-home-bar` / `#mp-home-ind` | Simulated home bar at the bottom |
| `#mp-hint` | Keyboard-shortcut hint strip, fixed at `bottom: 18px` |

---

## Button Position

The toggle button is pinned to the **bottom-right** of the viewport:

```css
.mp-btn {
    position: fixed;
    bottom: 72px;
    right: 20px;
    z-index: 10001;
}
```

The `bottom: 72px` offset ensures it sits above any browser UI chrome (e.g. mobile address bar or cookie banner) without overlapping the Bootstrap pager controls, which sit lower in the normal document flow.

---

## Class API

```js
class MobilePreviewToggle {
    constructor()        // Guards against iframe re-entry; injects styles; creates button
    injectStyles()       // Creates <style id="mp-styles"> in <head> (idempotent)
    createButton()       // Appends .mp-btn to document.body
    buildOverlay()       // Builds full #mp-overlay DOM tree (idempotent)
    activate()           // Shows overlay; updates button label/class
    deactivate()         // Hides overlay; restores button label/class
    toggle()             // Calls activate() or deactivate() based on this.isActive
}
```

Initialisation:

```js
document.addEventListener('DOMContentLoaded', () => {
    new MobilePreviewToggle();
});
```

---

## Key Design Decisions

| Decision | Reason |
|---|---|
| `src = window.location.href` | The preview always reflects the live page including any active filters, sort state, and page number |
| `window.self !== window.top` guard | Prevents the iframe from spawning a second button and second overlay inside itself |
| Styles injected into `<head>` rather than a stylesheet | No extra HTTP request; styles are tightly coupled to this one widget |
| `display: flex` toggle via `.mp-show` | Avoids recreating the DOM on repeated open/close cycles |
| Overlay built lazily (`buildOverlay` on first `activate()`) | No DOM overhead on pages where the user never clicks the button |
| `z-index: 10001` for button, `10000` for overlay | Button remains clickable above the overlay on the first paint frame |

---

## Scope

The `MobilePreviewToggle` is currently only included in:

- `resources/views/invoice/inv/guest.php` — the client-facing invoice list

It is **not** included in `resources/views/invoice/inv/index.php` (the admin invoice list) or any other view. To add it to another view, copy the `$mobilePreviewScript` heredoc block and the corresponding `echo Html::script(...)` line.

---

## Related Files

| File | Role |
|---|---|
| `resources/views/invoice/inv/guest.php` | Source of the `MobilePreviewToggle` class and its inline styles |
| `src/typescript/mobile-preview.ts` | TypeScript stub for the same concept (separate implementation) |
| `docs/INVOICE_AMOUNT_MAGNIFIER.md` | Documents the companion `InvoiceAmountMagnifier` class in the same view |
