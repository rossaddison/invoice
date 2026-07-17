<?php

declare(strict_types=1);

namespace App\Invoice\System\Console;

use App\Invoice\System\PhpVersionCheckService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 * Checks php.net for a newer PHP patch release on the running major.minor
 * branch and caches the result for the System Updates settings tab.
 *
 * Usage (cron, daily):
 *   php yii system/check-php-version
 */
final class CheckPhpVersionCommand extends Command
{
    protected static string $defaultName = 'system/check-php-version';

    public function __construct(
        private readonly PhpVersionCheckService $service,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Check php.net for a newer PHP patch release and cache the result.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PHP Version Check');

        $result = $this->service->check();

        if ($result->error !== null) {
            $io->error("Check failed: {$result->error}");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $io->writeln("Running:  {$result->currentVersion}");
        $io->writeln('Latest:   ' . ($result->latestVersion ?? 'unknown'));

        if (!$result->isOutdated) {
            $io->success('PHP is up to date.');
            return ExitCode::OK;
        }

        $message = "A newer PHP release is available: {$result->latestVersion}";
        if ($result->isSecurityRelease) {
            $io->error("{$message} (security release)");
        } else {
            $io->warning($message);
        }

        return $result->isSecurityRelease ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }
}
