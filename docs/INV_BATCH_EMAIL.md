# Batch Email — Send Selected Invoices to Clients

## Overview

The **☑️📧 Email Client** button on `inv/index` lets you select multiple
invoices via their checkboxes and send one consolidated email per client,
with all the client's selected invoice PDFs attached.

## How It Works

1. Tick one or more invoice checkboxes on `inv/index`.
2. Click **☑️📧 Email Client** in the toolbar — a Bootstrap 5 modal opens.
3. A loading spinner appears while the system fetches a per-client preview
   (resolving the destination email for each client group).
4. The preview table shows: **Client name**, **Email address**,
   **Source** (User account or Client record), **Invoice count**.
5. Choose an **Email Template** from the dropdown (only templates of type
   `invoice` are shown).
6. Click **📧 Email Client** to confirm.
7. One email is sent per client with all their PDFs attached, every
   selected invoice is marked **Sent** (status 2), and an `InvSentLog`
   entry is created for each.

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
| `InvBatchEmailService` | Core service: groups by client, resolves email, builds table, sends, logs |
| `InvBatchEmailDeps` | Value object bundling 8 deps (avoids SonarQube S107) |
| `Trait/BatchEmail.php` | `batchEmailPreview` + `batchEmail` controller actions |
| `InvsToolbar::buildBatchEmailButton()` | Toolbar button HTML |
| `InvsToolbar::buildBatchEmailModal()` | Bootstrap 5 modal HTML (preview table + template select) |
| `MailerHelper::yiiMailerSend()` | Signature changed from `?string` to `array $pdfPaths` to support multiple attachments |
| `invoice.ts` | `show.bs.modal` → preview fetch; `#batch-email-confirm` → send |

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

## Psalm

Psalm errorLevel 1: zero errors (June 2026).
