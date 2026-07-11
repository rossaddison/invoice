<?php

declare(strict_types=1);

namespace App\Command;

use App\Invoice\Mcp\InvoiceCapabilities;
use Mcp\Server as McpServer;
use Mcp\Server\Transport\StdioTransport;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Starts the Yii3-i MCP server over stdio so AI clients (Claude Desktop,
 * Cursor, etc.) can drive invoice workflows via natural language.
 *
 * Claude Desktop config (%APPDATA%\Claude\claude_desktop_config.json):
 *   {
 *     "mcpServers": {
 *       "yii3-i": {
 *         "command": "php",
 *         "args": ["C:/wamp64/www/invoice/yii", "mcp/serve"]
 *       }
 *     }
 *   }
 */
final class McpServeCommand extends Command
{
    protected static string $defaultName = 'mcp/serve';

    public function __construct(
        private readonly ContainerInterface $container,
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
        $server = McpServer::builder()
            ->setServerInfo('Yii3-i Invoice Server', '1.0.0')
            ->setContainer($this->container)
            ->addTool([InvoiceCapabilities::class, 'listInvoices'])
            ->addTool([InvoiceCapabilities::class, 'getInvoice'])
            ->addTool([InvoiceCapabilities::class, 'createInvoice'])
            ->addTool([InvoiceCapabilities::class, 'sendViaPeppol'])
            ->addTool([InvoiceCapabilities::class, 'peppolStatus'])
            ->addTool([InvoiceCapabilities::class, 'listClients'])
            ->addTool([InvoiceCapabilities::class, 'getClient'])
            ->addTool([InvoiceCapabilities::class, 'listPurchaseEntries'])
            ->build();

        $server->run(new StdioTransport());

        return ExitCode::OK;
    }
}
