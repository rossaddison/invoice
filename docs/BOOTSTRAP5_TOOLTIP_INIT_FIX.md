# Bootstrap 5 Tooltip Initialisation Fix

## Problem

`data-bs-toggle="tooltip"` info circles in settings pages (e.g. the font-size
setting in the Bootstrap 5 tab) showed no tooltip on hover.

## Root Cause

Two compounding issues:

### 1. Wrong script load order

In `resources/views/layout/invoice.php` Bootstrap was registered **after** the
IIFE TypeScript bundle:

```php
// Before (broken)
$assetManager->register(InvoiceNodeModulesAsset::class);   // IIFE loads first
$assetManager->register(BootstrapJsOnlyAsset::class);      // Bootstrap loads second
```

Because both `<script>` tags are placed at the bottom of `<body>`, scripts
execute in the order they appear in the HTML.  When the IIFE ran, `window.bootstrap`
did not exist yet, so every tooltip-init call returned early.

### 2. DOMContentLoaded listener never fired

`initializeTooltips()` in `index.ts` registered a `DOMContentLoaded` listener
to initialise tooltips.  Scripts placed at the bottom of `<body>` execute
**after** `DOMContentLoaded` has already fired, so the listener was registered
but never called.

A secondary bug in the same listener used a bare `bootstrap` identifier instead
of `(window as any).bootstrap`.  Inside an esbuild IIFE bundle the bare name is
not in scope and evaluates to `undefined`.

## Fix

### `resources/views/layout/invoice.php`

Register Bootstrap **before** `InvoiceNodeModulesAsset` so
`bootstrap.bundle.js` executes first and `window.bootstrap` is defined when the
IIFE runs.

```php
// After (fixed) — Bootstrap first, IIFE second
$assetManager->register($bootstrap5CdnNotNodeModule ? BsCdn::class : BsNm::class);
$assetManager->register($invCdnNotNodeModule ? InvCdn::class : InvNm::class);
$assetManager->register(NProgressAsset::class);
```

### `src/typescript/index.ts` — `initializeTooltips()`

- Removed the dead `DOMContentLoaded` wrapper.
- Changed bare `bootstrap` to `(window as any).bootstrap`.
- Switched from `new Tooltip(el)` to `Tooltip.getOrCreateInstance(el)` to
  prevent duplicate instances when the function is called more than once.

```typescript
private initializeTooltips(): void {
    const bs = (window as any).bootstrap;
    if (!bs?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
        try {
            bs.Tooltip.getOrCreateInstance(element as Element);
        } catch (error) {
            console.warn('Tooltip initialization failed:', error);
        }
    });
}
```

### `src/typescript/scripts.ts` — `initTooltips()`

Same `getOrCreateInstance` change applied to the exported helper used after
HTMX DOM swaps.

```typescript
export function initTooltips(): void {
    const bs = (window as any).bootstrap;
    if (!bs?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        try {
            bs.Tooltip.getOrCreateInstance(el);
        } catch (e) {}
    });
}
```

## Re-initialising tooltips after HTMX DOM swaps

`iPageSizeRefresh()` in `layout/invoice.php` replaces `#main-area` via
`fetch` + `DOMParser` + `replaceWith`.  After `htmx.process(fresh)` the new
DOM is live but Bootstrap tooltips on the replacement nodes are not yet bound.
Call `initTooltips()` (exposed on `window.InvoiceApp`) after the swap if
settings partials with info circles are inside `#main-area`.

## Files Changed

| File | Change |
|------|--------|
| `resources/views/layout/invoice.php` | Bootstrap registered before IIFE |
| `src/typescript/index.ts` | Fixed `initializeTooltips()` — no DOMContentLoaded, `(window as any).bootstrap`, `getOrCreateInstance` |
| `src/typescript/scripts.ts` | `initTooltips()` uses `getOrCreateInstance` |
| `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js` | Rebuilt bundle |
