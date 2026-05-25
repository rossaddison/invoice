# Global Page Size Navbar Selector — Yii3-i

## Summary

The per-view `PageSizeLimiter` widget was removed from 27 view files and 3 widget
classes and replaced by a single `<select>` element in the invoice layout navbar,
backed by a TypeScript `PageSizeHandler`.

---

## What Changed

### Removed
- `PageSizeLimiter` widget instantiation from 27 individual view files
- 3 widget classes that previously rendered the per-view limiter
- Dark mode variant of the page-size selector (simplified to single implementation)

### Added
- Single `<select>` in the invoice layout navbar (`resources/views/invoice/layout/`)
- TypeScript `PageSizeHandler` class wired to the navbar select element
- Selection persisted via session so the chosen page size survives navigation

### Fixed alongside
- `BootstrapJsOnlyAsset` hash-collision that caused `window.bootstrap` to be
  undefined when two asset bundles referenced the same Bootstrap JS source
- `CustomFieldRepository` PSR-4 path corrected (class not found under autoloading)

---

## How It Works

1. User selects a page size from the navbar dropdown (e.g. 10, 25, 50, 100)
2. `PageSizeHandler` posts the value to a lightweight endpoint
3. The endpoint writes the value to the user session
4. All paginated views read the session value via `SettingRepository::getPageSize()`
5. No full page reload — the current list view refreshes via HTMX partial swap

---

## TypeScript Integration

`PageSizeHandler` is bundled into `invoice-typescript-iife.js` alongside the other
TypeScript modules. After editing the TypeScript source, rebuild with:

```bash
npm run build:typescript
```

Then copy the updated bundle to the published assets directory:

```
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js
  →  public/assets/<hash>/rebuild/js/invoice-typescript-iife.js
```

See [TYPESCRIPT_BUILD_PROCESS.md](TYPESCRIPT_BUILD_PROCESS.md) for full build details.
