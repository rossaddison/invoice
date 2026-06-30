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

## Psalm

Psalm errorLevel 1: zero errors (June 2026).
