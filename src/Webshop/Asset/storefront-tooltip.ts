/**
 * Activates Bootstrap tooltips on the storefront — `data-bs-toggle="tooltip"`
 * markup does nothing on its own; Bootstrap 5 never auto-initializes
 * tooltips, something has to call `new bootstrap.Tooltip(element)` (or
 * `getOrCreateInstance`) itself. The staff app's own equivalent
 * (`src/typescript/index.ts`'s `initializeTooltips()`) isn't loaded here —
 * the storefront deliberately keeps its own, far smaller asset footprint
 * (see `resources/views/layout/templates/storefront/main.php`'s own
 * docblock) — so this is a standalone copy of the same few lines, not a
 * shared import across the two otherwise-separate TypeScript builds
 * (`tsconfig.json` only covers `src/typescript/**`; this file is compiled
 * by its own esbuild command, `build:typescript:webshop` in package.json,
 * same as cart.ts/price-range.ts/product-zoom.ts).
 *
 * Currently the only tooltip on `/shop` at all: the "Change currency"
 * dropdown's info icon (App\Invoice\Setting\Trait\SettingTooltipTrait's
 * `webshop_currency_preference` entry) — but this isn't wired to that one
 * spot specifically, so any future `data-bs-toggle="tooltip"` element here
 * gets it for free.
 */

// The `export {}` below is what makes this a module rather than a plain
// script — required for `declare global` augmentation syntax to be
// valid at all (TS2669), and invisible to esbuild's IIFE bundling.
declare global {
    var bootstrap:
        | {
              Tooltip: {
                  getOrCreateInstance(element: Element): unknown;
              };
          }
        | undefined;
}

export {};

// Bare DOMContentLoaded listener, no document.readyState guard — same
// convention as this directory's own cart.ts, which relies on this app's
// script tags being registered ahead of DOMContentLoaded firing.
document.addEventListener('DOMContentLoaded', () => {
    const bs = globalThis.bootstrap;
    if (!bs?.Tooltip) return;
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
        try {
            bs.Tooltip.getOrCreateInstance(element);
        } catch (error) {
            console.warn('Storefront tooltip initialization failed:', error);
        }
    });
});
