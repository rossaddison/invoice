<?php

declare(strict_types=1);

namespace App\Invoice\PaymentInformation\GatewayStatus\Console;

use App\Invoice\PaymentInformation\GatewayStatus\GatewayStatusService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Resolves each gateway's SDK version from composer.lock (bumping
 * last_updated only when it actually changed), rewrites
 * resources/gateway-status/gateways.json, and syncs every row into the
 * gateway_status SQLite database. Run after `composer update`, or as part
 * of the weekly gateway-status GitHub Actions workflow.
 *
 * Usage:
 *   php yii gateway-status/rebuild
 */
final class RebuildGatewayStatusCommand extends Command
{
    protected static string $defaultName = 'gateway-status/rebuild';

    public function __construct(
        private readonly GatewayStatusService $service,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription(
            'Resolve gateway SDK versions from composer.lock and sync gateways.json into the gateway_status database.',
        );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Rebuild Gateway Status');

        $rows = $this->service->loadFromJson();
        $rows = $this->service->resolveVersionsFromComposerLock($rows);
        $this->service->saveToJson($rows);
        $this->service->syncToDatabase($rows);

        $io->writeln('Synced ' . count($rows) . ' gateway row(s).');
        $io->success('Gateway status rebuilt.');
        return ExitCode::OK;
    }
}
