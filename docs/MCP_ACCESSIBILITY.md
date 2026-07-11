# MCP and Voice Accessibility — Why the Yii3-i MCP Server Matters for Users with Disabilities

Yii3-i's Phase 1 MCP server (see [MCP_SERVER_PLAN.md](MCP_SERVER_PLAN.md)) was built to let AI
assistants drive invoicing workflows in natural language. That same capability has a direct,
practical accessibility benefit: it lets someone operate the entire invoicing system by
**speaking**, without ever touching a mouse, keyboard, or touchscreen.

---

## What MCP is, in plain terms

The Model Context Protocol (MCP) is an open standard, published by Anthropic in November 2024,
that lets an AI assistant call into an external application's real functions — not just talk
about them. Instead of a chatbot that can only describe how to create an invoice, an
MCP-connected assistant can actually call `invoice_create`, `invoice_send_peppol`, or
`client_get` against Yii3-i and get a real result back.

Yii3-i exposes eight such tools today (`invoice_list`, `invoice_get`, `invoice_create`,
`invoice_send_peppol`, `peppol_status`, `client_list`, `client_get`, `purchase_entry_list`) via
`php yii mcp/serve`, connected to Claude Desktop / Claude Code / Cursor over stdio. No UI code
changed — the MCP server sits alongside the existing web app and calls the same repositories and
services (`InvRepository`, `InvService`, `PeppolSendService`, etc.).

---

## Why this matters for users with disabilities

The Yii3-i web UI is a conventional multi-step interface: GridView tables, toolbar buttons,
Bootstrap modals, HTMX partial swaps, multi-tab forms. That works well for sighted mouse/keyboard
users, but it puts real load on anyone who can't comfortably use a pointer, read dense tables, or
sustain many small precise interactions. Voice input through an MCP-connected assistant collapses
that multi-step interaction into one spoken sentence, which helps in several concrete ways:

- **Motor and dexterity impairments** (RSI, tremor, limited fine motor control, single-handed use)
  — commands like *"Create a draft invoice for Acme Ltd dated today"* or *"Send invoice 42 to the
  client via Peppol"* replace what would otherwise be a form, a dropdown, a save click, a toolbar
  click, and a confirmation modal.
- **Blind and low-vision users** — a screen reader still has to walk the DOM of a GridView or
  modal to find the right row and button. A voice request goes straight to the right tool call and
  comes back as a short spoken/read confirmation, with no table navigation required at all.
- **Chronic pain / fatigue conditions** — fewer physical interactions per task means fewer
  triggered symptoms over a working day of invoicing.
- **Cognitive load** — the assistant already knows the tool names and required fields; the user
  doesn't need to remember which menu, which settings tab, or which multi-step wizard (e.g. the
  credit-note or batch-email flows) a given action lives under.

This complements, rather than replaces, the existing accessibility work already in the codebase —
e.g. the `outline: none` removal in [BOOTSTRAP3_CSS_REMOVAL.md](BOOTSTRAP3_CSS_REMOVAL.md) for
WCAG 2.1 keyboard-focus visibility. Voice-driven MCP access is an additional path to the same
functionality, for people the visual UI serves less well.

---

## What a voice session looks like today

Dictation (Windows Voice Access, macOS Voice Control, or a phone's built-in mic keyboard) typed
into Claude Desktop's chat box already reaches the Yii3-i MCP server — no extra integration work
is required for this to work today:

```
User (spoken) → "Show me invoices from Acme Ltd that are still unpaid."
Claude        → calls invoice_list(statusId=2)   (Sent, not yet Paid)
Yii3-i        → returns matching invoices
Claude        → reads/prints a short summary back

User (spoken) → "Send the first one via Peppol."
Claude        → calls invoice_send_peppol(invoiceId=…)
Yii3-i        → queues the AS4 message, returns { status, message_id }
Claude        → "Done — queued for Peppol delivery."
```

No mouse, no table scanning, no modal navigation.

---

## Roadmap: voice inside the app itself

Phase 2 of the MCP plan (Angular `AiAssistantComponent` + `McpToolProxyService`, from
19 July 2026 — see [MCP_SERVER_PLAN.md](MCP_SERVER_PLAN.md#phase-2--typescript-mcp-client-angular))
brings the same tool-calling loop directly into the Yii3-i web UI as a chat panel, which is the
natural place to add a microphone button using the browser's built-in `SpeechRecognition` API —
so voice-driven invoicing won't require a separate desktop AI client at all.

---

## References

- [MCP Server Plan — Yii3-i as an AI-Accessible Invoice Service](MCP_SERVER_PLAN.md)
- [MCP Specification (2025-11-25)](https://modelcontextprotocol.io/specification/2025-11-25)
- [WCAG 2.1](https://www.w3.org/TR/WCAG21/)
