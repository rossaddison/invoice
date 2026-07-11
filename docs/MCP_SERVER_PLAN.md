# MCP Server Plan — Yii3-i as an AI-Accessible Invoice Service

Expose Yii3-i's invoice creation and Peppol send path as Model Context Protocol (MCP)
tools so AI assistants (Claude Desktop, Claude.ai, Cursor, etc.) can drive invoicing
workflows in natural language.

**Timeline**
- **Phase 1 — PHP MCP Server**: July 2026 (current sprint)
- **Phase 2 — TypeScript MCP Client (Angular)**: from 19 July 2026, targeting the
  MCP `2026-07-28` spec stable release

---

## Background

MCP (Model Context Protocol) is an open standard published by Anthropic (November 2024)
that defines how AI assistants communicate with external tools and data sources. It
replaces the M×N bespoke-integration problem with M+N: build your service as an MCP
server once, and any MCP-compatible AI client can use it.

The official PHP SDK (`mcp/sdk`, maintained by the PHP Foundation and Symfony project)
implements the MCP 2025-11-25 stable spec and is framework-agnostic with full PSR
compliance (PSR-7, PSR-11, PSR-14, PSR-15) — all of which Yii3-i already satisfies.

---

## Phase 1 — PHP MCP Server

### Installation

```bash
composer require mcp/sdk
```

The SDK requires PHP 8.1+. Yii3-i is on PHP 8.4 — no conflicts.

### Architecture

The inversion of control is deliberate: the SDK's auto-discovery instantiates classes
itself, bypassing Yii3's DI container. The bridge is a dedicated `InvoiceCapabilities`
class that receives all required services via its constructor (injected by Yii3's DI),
and its methods are registered manually with `->addTool([$capabilities, 'method'])`.

```
php yii mcp/serve (stdio)
  └── McpServeCommand
        └── Server::builder()
              ├── addTool([$capabilities, 'listInvoices'])
              ├── addTool([$capabilities, 'getInvoice'])
              ├── addTool([$capabilities, 'createInvoice'])
              ├── addTool([$capabilities, 'sendViaPeppol'])
              ├── addTool([$capabilities, 'peppolStatus'])
              ├── addTool([$capabilities, 'listClients'])
              ├── addTool([$capabilities, 'getClient'])
              └── addTool([$capabilities, 'listPurchaseEntries'])
```

### Tools

| Tool | Calls | Description |
|---|---|---|
| `invoice_list` | `InvRepository::findAllPreloaded()` | List invoices with optional date/status filters |
| `invoice_get` | `InvRepository::repoInvQuery()` | Get one invoice by ID |
| `invoice_create` | `InvService::saveInv()` | Create a draft invoice |
| `invoice_send_peppol` | `PeppolSendService::send()` | Trigger Peppol dispatch for an invoice |
| `peppol_status` | `As4MessageRepository` | Check AS4 delivery status for a message |
| `client_list` | `ClientRepository::findAllPreloaded()` | List clients |
| `client_get` | `ClientRepository::repoClientQuery()` | Get one client by ID |
| `purchase_entry_list` | `PurchaseEntryRepository::getReader()` | List received purchase invoices |

### New Files

| File | Purpose |
|---|---|
| `src/Command/McpServeCommand.php` | Symfony Console command — stdio entry point |
| `src/Invoice/Mcp/InvoiceCapabilities.php` | Tool handler methods, injected with Yii3 services |
| `src/Invoice/Mcp/InvoiceCapabilitiesInterface.php` | Interface for DI binding |
| `config/common/di/mcp.php` | DI wiring for `InvoiceCapabilities` |

### `McpServeCommand` skeleton

```php
// src/Command/McpServeCommand.php
final class McpServeCommand extends Command
{
    protected static string $defaultName = 'mcp/serve';

    public function __construct(
        private readonly InvoiceCapabilitiesInterface $capabilities,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Start the Yii3-i MCP server (stdio transport).');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $server = Server::builder()
            ->setServerInfo('Yii3-i Invoice Server', '1.0.0')
            ->addTool([$this->capabilities, 'listInvoices'])
            ->addTool([$this->capabilities, 'getInvoice'])
            ->addTool([$this->capabilities, 'createInvoice'])
            ->addTool([$this->capabilities, 'sendViaPeppol'])
            ->addTool([$this->capabilities, 'peppolStatus'])
            ->addTool([$this->capabilities, 'listClients'])
            ->addTool([$this->capabilities, 'getClient'])
            ->addTool([$this->capabilities, 'listPurchaseEntries'])
            ->build();

        $server->run(new StdioTransport());
        return ExitCode::OK;
    }
}
```

### `InvoiceCapabilities` skeleton

```php
// src/Invoice/Mcp/InvoiceCapabilities.php
final class InvoiceCapabilities implements InvoiceCapabilitiesInterface
{
    public function __construct(
        private readonly InvRepositoryInterface           $invRepo,
        private readonly InvServiceInterface              $invService,
        private readonly PeppolSendService                $peppolSend,
        private readonly As4MessageRepositoryInterface    $as4MessageRepo,
        private readonly ClientRepositoryInterface        $clientRepo,
        private readonly PurchaseEntryRepositoryInterface $purchaseEntryRepo,
        private readonly LoggerInterface                  $logger,
    ) {}

    #[McpTool(description: 'List invoices. Optional: from (YYYY-MM-DD), to (YYYY-MM-DD), statusId (1=draft,2=sent,3=viewed,4=paid).')]
    public function listInvoices(?string $from = null, ?string $to = null, ?int $statusId = null): array
    {
        // ...
    }

    #[McpTool(description: 'Get a single invoice by its integer ID.')]
    public function getInvoice(int $id): array
    {
        // ...
    }

    #[McpTool(description: 'Create a draft invoice for an existing client.')]
    public function createInvoice(int $clientId, string $dateCreated, ?string $note = null): array
    {
        // ...
    }

    #[McpTool(description: 'Send an invoice via Peppol AS4. Returns queued message ID.')]
    public function sendViaPeppol(int $invoiceId): array
    {
        // ...
    }

    #[McpTool(description: 'Check the Peppol AS4 delivery status of a sent message.')]
    public function peppolStatus(string $messageId): array
    {
        // ...
    }

    #[McpTool(description: 'List all clients.')]
    public function listClients(?string $search = null): array
    {
        // ...
    }

    #[McpTool(description: 'Get a single client by their integer ID.')]
    public function getClient(int $id): array
    {
        // ...
    }

    #[McpTool(description: 'List received purchase invoices. Optional: from/to date filters.')]
    public function listPurchaseEntries(?string $from = null, ?string $to = null): array
    {
        // ...
    }
}
```

### Console registration

`config/console/params.php`:

```php
'mcp/serve' => McpServeCommand::class,
```

### Claude Desktop config (local use)

```json
{
  "mcpServers": {
    "yii3-i": {
      "command": "php",
      "args": ["C:/wamp64/www/invoice/yii", "mcp/serve"]
    }
  }
}
```

After this, Claude Desktop can accept natural-language invoice commands and drive the
full Peppol send path without any custom UI.

### HTTP transport (production)

For remote access (Claude.ai, deployed agents), add an HTTP endpoint via a new
`McpController` using `StreamableHttpTransport`. An API key middleware guards the route.
This is additive — the stdio transport continues working alongside it.

### Quality gates

- Psalm errorLevel 1 clean on all new files before merge
- PHPUnit tests for `InvoiceCapabilities` tool methods (mock repositories)
- `vendor/bin/psalm --no-cache src/Command/McpServeCommand.php src/Invoice/Mcp/`

---

## Phase 2 — TypeScript MCP Client (Angular)

**Start date: 19 July 2026** — after the MCP `2026-07-28` spec stable release and the
`@modelcontextprotocol/client@2.0.0` stable npm package.

Yii3-i's Angular app (`angular/`) already uses TypeScript `^7.0.2`, which is ahead of
the MCP SDK's requirement of `^5.9.3` — no compatibility issues expected.

### npm packages

```bash
npm install @modelcontextprotocol/client@2.0.0   # stable from 28 July 2026
npm install @anthropic-ai/sdk
```

### Architecture

```
Angular UI (TypeScript)
  └── AiAssistantComponent
        ├── AnthropicClient  → Claude API (streams tool calls)
        └── McpToolProxy     → POST /mcp (PHP HTTP endpoint)
                                  └── McpController (Yii3)
                                        └── InvoiceCapabilities (same tools)
```

### New Angular files

| File | Purpose |
|---|---|
| `angular/src/app/ai-assistant/ai-assistant.component.ts` | Chat panel component |
| `angular/src/app/ai-assistant/mcp-tool-proxy.service.ts` | Proxies tool calls to `/mcp` |
| `angular/src/app/ai-assistant/ai-assistant.module.ts` | Module declaration |

### Example conversation

```
User → "Send invoice INV-042 to Acme Ltd via Peppol"

Claude → calls invoice_get(id=42)
PHP   → returns invoice data

Claude → calls client_get(id=<acme_id>)
PHP   → returns Peppol endpoint details

Claude → calls invoice_send_peppol(invoiceId=42)
PHP   → queues AS4 message, returns { messageId: "..." }

Claude → "Done — INV-042 has been queued for Peppol delivery."
```

### Auth

The `/mcp` HTTP endpoint requires an `Authorization: Bearer <api-key>` header. The key
is stored in `.env` as `MCP_API_KEY` and checked in `McpController` before dispatching
to the transport handler.

---

## Effort Estimate

| Step | Phase | Estimate |
|---|---|---|
| `composer require mcp/sdk` + verify no conflicts | 1 | 15 min |
| `InvoiceCapabilities` + interface + DI wiring | 1 | 3 h |
| `McpServeCommand` + console registration | 1 | 1 h |
| PHPUnit tests + Psalm | 1 | 2 h |
| Claude Desktop local test | 1 | 30 min |
| HTTP `/mcp` endpoint + API key auth | 1 | 1.5 h |
| `AiAssistantComponent` (Angular) | 2 | 2 h |
| `McpToolProxyService` (Angular) | 2 | 1 h |
| End-to-end test (Angular → PHP → Peppol) | 2 | 1 h |

**Phase 1 total: ~8 hours**
**Phase 2 total: ~4 hours**

---

## References

- [MCP Specification (2025-11-25)](https://modelcontextprotocol.io/specification/2025-11-25)
- [mcp/sdk on Packagist](https://packagist.org/packages/mcp/sdk)
- [modelcontextprotocol/php-sdk on GitHub](https://github.com/modelcontextprotocol/php-sdk)
- [2026-07-28 MCP Spec Release Candidate](https://blog.modelcontextprotocol.io/posts/2026-07-28-release-candidate/)
- [@modelcontextprotocol/client v2 beta on npm](https://www.npmjs.com/package/@modelcontextprotocol/client)
