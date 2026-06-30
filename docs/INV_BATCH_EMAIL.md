# Batch Email — Send Selected Invoices to Clients

## Overview

The **☑️📧 Email Client** button on `inv/index` lets you select multiple
invoices via their checkboxes and send one consolidated email per client,
with all the client's selected invoice PDFs attached.

## How It Works

1. Tick one or more invoice checkboxes on `inv/index`.
2. Click **☑️📧 Email Client** in the toolbar — a Bootstrap 5 modal opens.
3. A loading spinner appears while the system fetches a per-invoice preview
   (resolving the destination email for each invoice).
4. The preview table shows: **Client name**, **Email address**,
   **Source** (User account or Client record), **Invoice count**.
5. Choose a **From Email** address from the dropdown (populated from
   `FromDropDown` records — see [Verified Sender](#verified-sender-fromdropdown)
   below).  Click ➕ to add a new address without leaving the flow.
6. Choose an **Email Template** from the dropdown (only templates of type
   `invoice` are shown).
7. Click **📧 Email Client** to confirm.
8. One email with one PDF is sent **per selected invoice**, every invoice
   is marked **Sent** (status 2), and an `InvSentLog` entry is created for each.

## Verified Sender (FromDropDown)

The **From Email** dropdown in the modal is populated from the `FromDropDown`
table (`/invoice/from`).  If the dropdown is empty:

1. Click ➕ next to the From Email selector.
2. Fill in the email address on the `/from/add` form and save.
3. You are automatically redirected back to `inv/index` with the batch
   email modal re-opened (`?openModal=batchEmail`).

The selected `from_dropdown_id` is passed to the `batchEmail` GET route.
The controller resolves it to a `FromDropDown::getEmail()` string and
forwards it to `InvBatchEmailService::sendBatch()` as `$selectedFromEmail`.
When non-empty this **overrides** the UserInv / EmailTemplate fallback chain.

> **Pre-existing fix:** `FromDropDownController::edit()` and `delete()` were
> redirecting to the non-existent route `'index'` instead of `'from/index'`.
> Both corrected in the same commit.

## Email Routing (UserClient Priority)

The destination address is resolved in this order:

| Priority | Condition | Email used |
|----------|-----------|------------|
| 1 | Client has a linked `UserClient` entry and that User has an email | `User::getEmail()` |
| 2 | No linked User, or User email is blank | `Client::getClientEmail()` |

Template variables (`{{{client_name}}}`, `{{{client_surname}}}`, etc.)
always resolve from the **Client** entity regardless of which address the
email is sent to.

## Email Template Variables

The `{{{invoice_table}}}` placeholder in the template body is replaced
with a responsive HTML table containing:

| Column | Source |
|--------|--------|
| Invoice # | `Inv::getNumber()` |
| Date | `Inv::getDateCreated()` |
| Total | `InvAmount::getTotal()` |
| Balance | `InvAmount::getBalance()` |
| Note | `Inv::getNote()` |
| Link | Guest URL via `Inv::getUrlKey()` |

All other template variables (`{{{client_*}}}`, `{{{userinv_*}}}`, etc.)
are processed by the existing `TemplateHelper::parseTemplate()`.

## Architecture

| Class / File | Role |
|---|---|
| `InvBatchEmailService` | Core service: iterates per invoice, resolves email, builds table, sends, logs |
| `InvBatchEmailDeps` | Value object bundling 8 deps (avoids SonarQube S107) |
| `Trait/BatchEmail.php` | `batchEmailPreview` + `batchEmail` controller actions; reads `from_dropdown_id` |
| `InvsToolbar::buildBatchEmailButton()` | Toolbar button HTML |
| `InvsToolbar::buildBatchEmailModal()` | Bootstrap 5 modal HTML (preview table + From dropdown + ➕ + template select) |
| `InvsToolbarParams` | Value object passed to toolbar; now includes `FromDropDownRepository $fdR` |
| `InvsListWidget` | `withFdR()` immutable setter wires `fdR` through to the toolbar |
| `InvIndexNavDeps` | `FDR $fdR` added so the repository reaches the widget via DI |
| `FromDropDownController::add()` | Accepts `?returnUrl=batchEmail`; redirects to `inv/index?openModal=batchEmail` on save |
| `MailerHelper::yiiMailerSend()` | Signature changed from `?string` to `array $pdfPaths` to support multiple attachments |
| `invoice.ts` | `show.bs.modal` → preview fetch; `#batch-email-confirm` → send with `from_dropdown_id`; auto-opens modal on `?openModal=batchEmail` |

## Routes

| Method | Path | Action |
|--------|------|--------|
| GET | `/inv/batchEmailPreview` | Returns JSON preview (client, email, source, invoice_count) |
| GET | `/inv/batchEmail` | Sends the batch; returns `{success: 1}` or `{success: 0}` |

Both routes require the same `editInv` permission as other `inv` actions.

## Translation Keys Added

- `email.client` → `Email Client`

## Known Pitfalls Fixed During Development

### `Html::tag()` encodes string content by default

`Html::tag('thead', $htmlString)` and `Html::tag('select', $optionHtml)` encode
their content, turning inner tags into escaped text visible in the browser.

**Fix:** use `Html::openTag()` / `Html::closeTag()` pairs for any element whose
content is already-rendered HTML. Affects `buildBatchEmailModal()` in
`InvsToolbar.php`.

### `DataResponseFactory` double-encodes JSON arrays

`$this->factory->createResponse(Json::encode($array))` wraps the JSON string
in a second encoding layer. `getJson<T>()` calls `JSON.parse()` once and
returns a string, not the array. `Array.isArray()` then fails and the preview
table body stays empty.

**Fix:** unwrap in TypeScript before the array check:

```typescript
const raw = await getJson<unknown>(url, { keylist: selected });
const rows: PreviewRow[] = Array.isArray(raw)
    ? (raw as PreviewRow[])
    : typeof raw === 'string'
        ? (JSON.parse(raw) as PreviewRow[])
        : [];
```

This matches the `parsedata()` pattern used by all other TypeScript handlers
that call `factory->createResponse(Json::encode(...))`.

### `pdf_stream_inv` setting silently blocks email sending

`InvPdfService::generate(int $invId, bool $stream, bool $custom)` — when
`$stream` is `true` the PDF is written directly to the HTTP response buffer
(browser download) and the method returns `''`.  If `pdf_stream_inv = '1'`
in Settings, passing that flag into the batch loop meant `$pdfPaths` stayed
empty, `yiiMailerSend` sent a mail with no attachment, and the underlying
`mailer->send()` threw (or silently returned false).

**Fix:** always pass `false` for `$stream` when generating PDFs for email
attachment — the setting only applies to the PDF-download button, never to
server-side file creation for attachments.  `Quote/Trait/Email.php` already
hardcodes `false`; batch email must match.

### Quote `Trait/Email.php` `yiiMailerSend` call site

Changing `MailerHelper::yiiMailerSend` from `?string` to `array $pdfPaths`
also required updating `src/Invoice/Quote/Trait/Email.php` to wrap the single
path: `[$pdf_template_target_path]`.

### Client-grouped loop sent only one email per client, not per invoice

The original `sendBatch()` built a `array<int, list<Inv>> $groups` keyed by
`clientId`, then ran one email per group.  Two invoices for the **same client**
produced a single email, so the second invoice was silently skipped from the
user's perspective.

**Fix (commit `763b8b5f`):** remove the grouping step entirely.  Iterate
directly over the selected `$invIds`, load each `Inv`, resolve its destination
email, generate its PDF, send one email, mark one invoice Sent.  This matches
the behaviour of the single-invoice `emailStage2` flow and ensures `n` selected
invoices always produce exactly `n` emails (minus any whose destination email
is blank).

### `FromDropDown::include` defaulting to `false` silently emptied the dropdown

The batch email modal filtered candidates with `if ($from->getInclude())`.
Because the `include` checkbox defaults to unchecked on the add form, every
newly created `FromDropDown` record had `include = false` and was excluded.

**Fix:** removed the PHP-level `getInclude()` filter in
`InvsToolbar::buildBatchEmailModal()`.  All `FromDropDown` records are now
shown in the selector; the `include` flag is persisted but no longer gates
visibility in the batch email dropdown.

## Psalm

Psalm errorLevel 1: zero errors (June 2026).
