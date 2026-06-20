# Invoice Copy — Multi-Client Selection & Workflow Badge Fix

**Branch:** `main`  
**Date:** June 2026

## Problems

### 1. Workflow badge showing wrong icon after bulk copy

`InvsColumnBuilder::buildWorkflowTypeColumn()` used `!== null` to detect whether
`so_id` or `quote_id` was set on an invoice:

```php
if ($model->getSoId() !== null) { … }   // fired even when so_id = 0
if ($model->getQuoteId() !== null) { … }
```

When a plain invoice was copied via the bulk-copy flow (`inv/multiplecopy`), the
database could store `so_id = 0` rather than `NULL`.  Because `0 !== null` is
`true`, the copied invoice incorrectly received the blue 🔀 Peppol-workflow badge
instead of the grey 📄 standalone-invoice badge.

### 2. No client selection in the bulk-copy modal

The `#modal-copy-inv-multiple` modal on `inv/index` offered only a date picker.
There was no way to copy the selected invoices to a different (or additional)
client — the copy always went to each invoice's own original client.

## Solutions

### Workflow badge fix

Both checks now use `> 0`, treating `null` and `0` identically:

```php
if (($model->getSoId() ?? 0) > 0) { … }
if (($model->getQuoteId() ?? 0) > 0) { … }
```

### Multi-client copy on `inv/index`

The `#modal-copy-inv-multiple` modal was extended with:

- A live-filter search input (`#copy-inv-multiple-client-search`)
- A scrollable checkbox list (`#copy-inv-multiple-client-list`) containing every
  client that has a user account — no client is pre-selected, allowing any
  combination to be chosen
- The `$clients` iterable is passed from `indexModalCopyInvMultiple()` using
  `ClientRepository::repoUserClient()` filtered by
  `UserClientRepository::getClientsWithUserAccounts()`

The `multiplecopy()` trait method was refactored to:

1. Read an optional `client_ids[]` array from the request
2. Fall back to each invoice's own client when no clients are selected
   (preserving backward compatibility)
3. Loop over every invoice × every selected client, creating one copy per pair
4. Call `ProductClientService::syncFromInvItems()` after each copy so the
   `product_client` pivot table is kept up to date

The TypeScript handler `handleCopyMultipleInvoices` was updated to:

- Collect all `input[name="copy_inv_multiple_client_ids[]"]:checked` values
- Alert and abort when no client is ticked
- Send `client_ids[]` alongside `keylist[]` and `modal_created_date`
- Filter the checkbox list live via the new `filterCopyMultipleClientList()` method

## Files changed

| File | Change |
|------|--------|
| `src/Invoice/Inv/Widget/InvsColumnBuilder.php` | `getSoId() !== null` → `> 0`; `getQuoteId() !== null` → `> 0` |
| `resources/views/invoice/inv/modal_copy_inv_multiple.php` | Added client search + checkbox list; rewritten to `Yiisoft\Html\Html as H` conventions (no raw HTML, 1-space indent, numeric `closeTag` comments) |
| `src/Invoice/Inv/Trait/Index.php` | `indexModalCopyInvMultiple()` accepts `iterable $clients`; call site passes `repoUserClient(getClientsWithUserAccounts())` |
| `src/Invoice/Inv/Trait/MultipleCopy.php` | `multiplecopy()` reads `client_ids[]`, loops per invoice × per client, syncs `ProductClient` |
| `src/typescript/invoice.ts` | `CopyMultipleInvoicesData` gains `client_ids`; handler collects checkboxes and validates; `filterCopyMultipleClientList()` added; `handleChange` routes `#copy-inv-multiple-client-search` |
| `src/Invoice/Asset/rebuild/js/invoice-typescript-iife.{js,min.js}` | Rebuilt (144.1 kb) |
| `public/assets/610659e5/rebuild/js/invoice-typescript-iife.{js,min.js}` | Published copy updated |

## Psalm

`vendor/bin/psalm --no-cache` — no errors on all changed PHP files (errorLevel 1).
