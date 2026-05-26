# TypeScript IIFE Bundle — Yii3-i

## Overview

All client-side TypeScript is compiled by **esbuild** into a single minified IIFE:

```
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js   134.6 KB
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.min.js   134.6 KB
```

The bundle is registered in `InvoiceAsset` and loaded once per page. It exposes
the global `InvoiceApp` object and pins `window.htmx`.

**Rebuild command:**

```bash
npm run build:typescript
```

**Build target:** ES2024 · **Format:** IIFE · **Global:** `InvoiceApp`

---

## Entry Point — `index.ts`

`InvoiceApp` class instantiated immediately on DOM-ready. Constructor:

| Step | What happens |
|------|-------------|
| Instantiates all handler classes | Keeps references so event listeners stay active |
| `initializeTooltips()` | Calls `bootstrap.Tooltip.getOrCreateInstance()` on every `[data-bs-toggle="tooltip"]` element |
| `initializeTaggableFocus()` | Global `focus` listener that stores the last `.taggable` element in `window.lastTaggableClicked` |
| `initializeFullpageLoader()` | Wires `.ajax-loader` → `showFullpageLoader()` and `.fullpage-loader-close` → `hideFullpageLoader()` |
| `initTooltips()` / `initSimpleSelects()` / `initPasswordMeter()` | Re-runs the `scripts.ts` helpers to catch elements added after initial render |

After `InvoiceApp` is created, these standalone init functions run:
`initStripePayment`, `initAmazonPayment`, `initBraintreePayment`,
`initTelegramProviderPopup`, `initStreetOrder`, `initStepPopovers`.

---

## Module Reference

### `htmx.ts`
Imports htmx 2.x from the npm package and assigns it to `window.htmx` so that
inline `hx-on::` event handlers in PHP views can reach it.

---

### `utils.ts` — Shared helpers

| Function | Purpose |
|----------|---------|
| `parsedata(data)` | Safe JSON parser — accepts string, object, or anything; always returns an object. Used by every handler to normalise fetch responses. |
| `getJson(url, params?, options?)` | HTTP GET via `fetch`. Arrays in `params` are serialised as `key[]=v1&key[]=v2` to match PHP's `$_GET` array parsing. Throws on non-2xx. |
| `closestSafe(element, selector)` | `element.closest(selector)` with a manual parent-walk fallback for edge cases. |
| `getElementById(id)` | Typed wrapper for `document.getElementById`. |
| `querySelector(selector)` | Typed wrapper for `document.querySelector`. |
| `querySelectorAll(selector)` | Typed wrapper for `document.querySelectorAll`. |
| `getInputValue(id)` | Gets `.value` from a form input by id; returns `''` if not found. |
| `processBatchWithProgress(items, processor, options)` | ES2024 `Promise.withResolvers`-based batch processor. Runs `processor` on items in configurable batches with per-item timeout, exponential-backoff retry, and an `onProgress` callback. |

---

### `scripts.ts` — Global UI utilities

| Function | Purpose |
|----------|---------|
| `initTooltips()` | Initialises Bootstrap 5 tooltips on all `[data-bs-toggle="tooltip"]` elements. Guards against missing `window.bootstrap`. |
| `initSimpleSelects(root?)` | Wraps every `.simple-select` element with TomSelect. Skips elements already initialised (`_tomselect` flag). Accepts an optional root to scope the search. |
| `showFullpageLoader()` | Shows `#fullpage-loader`, adds `.icon-spin` to `#loader-icon` (`bi bi-gear-fill`), hides the error panel. After 10 s reveals `#loader-error` and marks the icon `.text-danger`. |
| `hideFullpageLoader()` | Hides `#fullpage-loader`, resets icon state. |
| `initPasswordMeter()` | Binds `input` on `.passwordmeter-input`; scores strength 0–5 (length, lower, upper, digit, special); shows `.passmeter-2` at ≥ 3 and `.passmeter-3` at ≥ 4. |
| `calculatePasswordStrength(password)` | Internal scorer used by `initPasswordMeter`. Returns 0–5. |
| `initializeScripts()` | Auto-init wrapper called on module load; wires DOM-ready listeners. |

> **Icon note:** `.icon-spin` is defined in `overrides.css` (`@keyframes icon-spin`). It
> replaced the removed FontAwesome `fa-spin` class.

---

### `settings.ts` — `SettingsHandler`

Handles the Settings page form.

| Method | Purpose |
|--------|---------|
| `toggleSmtpSettings()` | Shows `#div-smtp-settings` when `#email_send_method` is `'smtp'`; hides it otherwise. |
| `handleFphGenerateClick()` | Async. Collects device fingerprint metrics (`userAgent`, `screen.width/height`, `devicePixelRatio`, `colorDepth`, `innerWidth/Height`) → GET `/setting/fphgenerate` → populates all `settings[fph_*]` input fields. |
| `updateSettingField(fieldId, value?)` | Sets `element.value` for a settings input by id. |
| `handleSettingsSubmitClick()` | Before submitting the settings form, temporarily sets all `.tab-pane` to `display:block` and enables disabled inputs so hidden-tab fields are included in the POST. Uses ES2024 `Array.toReversed()`. |
| `restoreFormState(tabPanes)` | Reverses the display/disabled changes made by `handleSettingsSubmitClick`. |
| `handleOnlinePaymentSelectChange()` | Reads `#online-payment-select` value; hides all `.gateway-settings`, then reveals `#gateway-settings-{driver}`. |

---

### `invoice.ts` — `InvoiceHandler`

Handles all operations on the invoice view page via a single delegated `click`
listener.

| Method | Trigger | Purpose |
|--------|---------|---------|
| `setButtonLoading(btn, isLoading, original?)` | Internal helper | Shows Bootstrap spinner on load; restores `original` HTML or a `bi-check-lg` on completion. |
| `handleMarkAsSent()` | `#btn-mark-as-sent` | GET `/inv/markAsSent` → updates status; reloads on success. |
| `handleInvToInvConfirm()` | `.inv_to_inv_confirm` | GET `/inv/inv_to_inv_confirm` with `inv_id`, `client_id`, `user_id` → navigates to new invoice on success. |
| `handleDeleteItem()` | `.btn_delete_item` | Deletes an invoice line item via fetch. |
| `handleEmailSend()` | email send button | Sends invoice email via fetch. |
| `handleAllClientsCheck()` | `#user_all_clients` | Fetches and populates clients dropdown based on "all clients" toggle. |
| `handleSelectAllCheckboxes(checked)` | `[name="checkbox-selection-all"]` | Checks/unchecks all product/task selection checkboxes. |
| `initializeAllClientsCheck()` | Constructor | Runs initial all-clients state check on page load. |

---

### `quote.ts` — `QuoteHandler`

Mirrors `InvoiceHandler` for the quote view. Delegated `click` listener.

| Method | Trigger | Purpose |
|--------|---------|---------|
| `setButtonLoading(btn, isLoading, original?)` | Internal helper | Bootstrap spinner / `bi-check-lg` restore. |
| `handleQuoteConfirm()` | `.select-items-confirm-quote` | Collects checked product IDs → GET `/quoteitem/addProduct` → adds products to quote. |
| `handleDeleteMultiple()` | delete-multiple button | Deletes multiple selected items. |
| `handleDeleteItem()` | `.btn_delete_item` | Deletes a single quote line item. |
| `handleEmailSend()` | email send button | Sends quote email. |
| Additional workflow methods | Various buttons | Copy, convert-to-invoice, PDF generation. |

---

### `client.ts` — `ClientHandler`

Manages client-related actions including note management and form flows.

| Function / Method | Purpose |
|-------------------|---------|
| `createSecureUIElement(type, className, iconClass)` | Creates `<type class="className"><i class="iconClass"></i></type>` via DOM APIs (no `innerHTML`) to avoid XSS. |
| `setButtonLoading(btn, isLoading, original?)` | Uses `createSecureUIElement` with `'spinner-border spinner-border-sm'` or `'bi bi-check-lg'`. |
| `setSecureButtonContent(btn, type, className, iconClass)` | Clears `btn` text and appends a secure UI element. |
| `handleDeleteClientNote()` | Async. DELETE to `/client/deleteClientNote` with `note_id`; removes the `.panel` from DOM on success. |
| `handleClientFormSubmit()` | Intercepts specific client form submissions; delegates to fetch. |
| Various RBAC/assignment handlers | Handles client-to-user assignment workflows. |

---

### `product.ts` — `ProductHandler`

Manages product lookup modals and product-related actions.

| Item | Purpose |
|------|---------|
| `BUTTON_ICONS` | Constant map: `loading → 'spinner-border spinner-border-sm'`, `success → 'bi bi-check-lg'`, `error → 'bi bi-x-lg'`. |
| `setSecureButtonContent(btn, type, className, iconClass)` | Shared safe DOM builder. |
| `handleQuoteConfirm()` | Collects selected product IDs → posts to `/quoteitem/addProduct`. |
| `handleProductSearch()` | Searches products by SKU/name via fetch; populates results table. |
| `handleProductSelect()` | Adds a selected product from the lookup modal into the active form. |
| `handleInvoiceProductAdd()` | Adds a product directly to an invoice line. |

---

### `create-credit.ts` — `CreateCreditHandler`

| Method | Purpose |
|--------|---------|
| `processCreateCredit()` | Async. Shows spinner; collects `inv_id`, `client_id`, `inv_date_created`, `group_id`, `password`, `user_id` from form → GET `/inv/createCreditConfirm` → shows `bi-check2-square` on success or `bi-x-lg` on failure; reloads page. |

---

### `salesorder.ts` — `SalesOrderHandler`

| Method | Trigger | Purpose |
|--------|---------|---------|
| `handleSoToInvoiceConversion()` | `.so_to_invoice_confirm` | Async. Shows Bootstrap spinner; collects `so_id`, `client_id`, `group_id`, `password` → GET `/salesorder/soToInvoiceConfirm` → navigates to the new invoice URL on success, shows `bi-check-lg` on partial success. |
| Additional order methods | Various buttons | Status updates, PDF, email actions on the sales order view. |

---

### `tasks.ts` — `TaskHandler`

| Method | Purpose |
|--------|---------|
| `selectTasksForEntity()` | Async. Sorts checked task IDs (`Array.toSorted` — ES2024); shows spinner on the confirm button; GET `/task/selection_inv` or `/task/selection_quote` with `task_ids[]` and entity id → calls `processTasks()` → shows `bi-check-lg`; reloads. |
| `processTasks(tasks)` | Processes the server response and updates the task table rows. |

---

### `family.ts` — `FamilyHandler`

| Method | Purpose |
|--------|---------|
| `getFamilyDataFromCheckedBoxes()` | Reads all checked family checkboxes and returns `{ family_id }` array. |
| `handleGenerateBatchProducts()` | Async. Shows spinner; collects family IDs, `tax_rate_id`, `unit_id`, CSRF token → POST `/family/generateProducts` (URL-encoded, `family_ids[]` repeated keys) → calls `handleGenerationSuccess`. |
| `handleGenerationSuccess(data, btn)` | Shows `bi-check-lg` on the button; alerts with count of generated products; closes the generate-products modal. |

---

### `cron.ts` — Standalone (NOT in the main bundle)

`cron.ts` is compiled separately and not imported by `index.ts`. It is used as a
standalone script on the settings page.

| Function | Purpose |
|----------|---------|
| `generateSecureHex(bytes)` | Generates a cryptographically random hex string of `bytes` length using `crypto.getRandomValues()`. |
| `escapeIdForQuerySelector(id)` | Escapes CSS selector special characters using `CSS.escape` with a bracket-escape fallback, so ids containing `[` and `]` (e.g. `settings[cron_key]`) can be used in `querySelector`. |
| `setButtonWorkingState(button, working)` | Stores original `innerHTML` in `button.__originalHTML`, sets `aria-busy`, shows Bootstrap spinner; restores on `working=false`. |
| `handleGenerateClick(button)` | Async. Generates a 48-char hex key → sets `settings[cron_key]` input value → tries `navigator.clipboard.writeText()` → shows `bi-check-lg` on copy success, `bi-arrow-repeat` on fallback or error. |
| `initGenerateCronKey()` | Attaches a `click` handler to `#btn_generate_cron_key`. Guards against double-binding. |

---

### Payment modules

| Module | Init function | Purpose |
|--------|--------------|---------|
| `payment-stripe.ts` | `initStripePayment()` | Reads `data-*` config from `#stripe-payment-config`; initialises Stripe.js v3 Elements and wires the payment form. |
| `payment-amazon.ts` | `initAmazonPayment()` | Reads config from hidden div; initialises the Amazon Pay button SDK. |
| `payment-braintree.ts` | `initBraintreePayment()` | Reads config from hidden div; initialises Braintree Drop-in UI. |

All three are no-ops if their config element is absent — safe to include on all pages.

---

### `telegram-providers.ts`

| Function | Purpose |
|----------|---------|
| `initTelegramProviderPopup()` | Moves `#telegram-providers` modal to `document.body` so Bootstrap can render it correctly when the triggering element sits inside an `overflow:hidden` ancestor (e.g. a tab pane). |

---

### `family-street-order.ts`

| Function | Purpose |
|----------|---------|
| `collectIds(list)` | Returns ordered array of `data-id` integers from `li[data-id]` children. |
| `refreshPositionBadges(list)` | Renumbers `.street-position` badge text to match the current DOM order. |
| `setStatus(el, message, type)` | Updates a status `<div>` with `alert-{type}` class and message text. |
| `initStreetOrder()` | Wires native HTML5 drag-and-drop (`dragstart`, `dragover`, `drop`) on `#street-order-list`; on drop, POSTs the new id order to `data-reorder-url` and calls `refreshPositionBadges`. |

---

### `google-translate-popover.ts`

| Function | Purpose |
|----------|---------|
| `formatStepContent(raw)` | Converts `---Step--N: description` lines (as stored in `data-bs-content`) into a Bootstrap `<ol class="mb-1 ps-3 small">` HTML string. |
| `initStepPopovers()` | Initialises Bootstrap popovers on every `[data-popover-steps]` element, passing the formatted HTML as the popover body. |

---

### `family-commalist-picker.ts`

Auto-initialised on import. Wires the comma-list picker widget that lets users
select family members and build a comma-separated value string in an input field.
Used on the family edit view.

---

## Icons Used in the Bundle

All dynamic icons are Bootstrap Icons (`bi bi-*`). FontAwesome has been fully
removed. Spinner states use Bootstrap 5's native `.spinner-border` component.

| State | Class / element |
|-------|----------------|
| Loading / working | `<span class="spinner-border spinner-border-sm" role="status"></span>` |
| Success | `<i class="bi bi-check-lg"></i>` |
| Error / cancel | `<i class="bi bi-x-lg"></i>` |
| Gear (fullpage loader) | `<i class="bi bi-gear-fill icon-spin"></i>` — animated via `@keyframes icon-spin` in `overrides.css` |
| Recycle / refresh | `<i class="bi bi-arrow-repeat me-1"></i>` |

---

## Build Commands

```bash
# Production build (minified, ES2024 target)
npm run build:typescript

# TypeScript type check only
npm run type-check

# Lint
npm run lint
```

**Output path:** `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js`

After rebuilding, copy the updated bundle to the published assets directory so
the browser receives the new file:

```
src/Invoice/Asset/rebuild/js/invoice-typescript-iife.js
  →  public/assets/<hash>/rebuild/js/invoice-typescript-iife.js
```

The `<hash>` folder is stable between builds — check `public/assets/` for the
existing folder name (e.g. `7246626a`).

---

## Source File Index

| File | Bundled | Purpose |
|------|---------|---------|
| `index.ts` | ✓ | Entry point; `InvoiceApp` class |
| `utils.ts` | ✓ | Shared fetch/DOM helpers |
| `types.ts` | ✓ | TypeScript type definitions |
| `scripts.ts` | ✓ | Tooltip, TomSelect, loader, password meter |
| `htmx.ts` | ✓ | Bundles htmx 2.x; pins `window.htmx` |
| `invoice.ts` | ✓ | `InvoiceHandler` |
| `quote.ts` | ✓ | `QuoteHandler` |
| `client.ts` | ✓ | `ClientHandler` |
| `product.ts` | ✓ | `ProductHandler` |
| `tasks.ts` | ✓ | `TaskHandler` |
| `salesorder.ts` | ✓ | `SalesOrderHandler` |
| `family.ts` | ✓ | `FamilyHandler` |
| `settings.ts` | ✓ | `SettingsHandler` |
| `create-credit.ts` | ✓ | `CreateCreditHandler` |
| `payment-stripe.ts` | ✓ | Stripe Elements init |
| `payment-amazon.ts` | ✓ | Amazon Pay init |
| `payment-braintree.ts` | ✓ | Braintree Drop-in init |
| `telegram-providers.ts` | ✓ | Modal DOM relocation |
| `family-street-order.ts` | ✓ | Drag-and-drop street reorder |
| `google-translate-popover.ts` | ✓ | Bootstrap popover step formatting |
| `family-commalist-picker.ts` | ✓ | Comma-list picker widget |
| `cron.ts` | ✗ | Standalone; loaded separately on settings page |
| `mobile-preview.ts` | ✗ | Dead code; mobile preview is inline in view files |
| `page-size.ts` | ✗ | Superseded by `PageSizeHandler` in navbar |

---

*Last updated: May 2026 — bundle 134.6 KB, esbuild ES2024 target, Bootstrap Icons, no FontAwesome*
