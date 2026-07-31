<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Console\ExitCode;

final class CacheClearCommand extends Command
{
    protected static string $defaultName = 'cache/clear';

    public function __construct(private readonly Aliases $aliases)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this->setDescription('Clear the runtime cache (DI, config, routes).');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $deleted = $this->clearFileCache($io);
        $apcuCleared = $this->clearApcuCache();

        $io->success(
            "Cache cleared — {$deleted} file(s) removed from @runtime/cache."
        );

        if ($apcuCleared) {
            $io->warning(
                'This command runs on the CLI, which has its own separate '
                . "APCu memory pool from Apache/PHP-FPM's — it cannot reach "
                . 'the web workers\' cache from here, only its own (already '
                . 'empty) one. If CacheInterface is bound to ApcuCache '
                . '(config/common/di/cache.php) and a route changed, restart '
                . 'the web server instead (e.g. `rc-service apache2 restart` '
                . 'on Alpine) — that is what actually clears the live APCu '
                . 'cache the web workers are serving from. See '
                . 'docs/YII_ENV_ROUTE_CACHE_AND_DEPLOY_JULY_2026.md §6.'
            );
        }

        return ExitCode::OK;
    }

    private function clearFileCache(SymfonyStyle $io): int
    {
        $cacheDir = $this->aliases->get('@runtime/cache');

        if (!is_dir($cacheDir)) {
            $io->warning("Cache directory not found: {$cacheDir}");
            return 0;
        }

        $deleted = 0;
        /** @var \SplFileInfo $item */
        foreach (new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($cacheDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        ) as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            ++$deleted;
        }

        return $deleted;
    }

    /**
     * IMPORTANT LIMITATION: APCu's shared memory is scoped per SAPI — the
     * CLI process running this very command has its own separate pool from
     * Apache/PHP-FPM's web workers, and PHP never shares the two, even with
     * apc.enable_cli=1. So this call cannot clear the cache the web workers
     * are actually serving from; it only clears (an already near-empty)
     * CLI-local pool. Kept anyway because it's harmless and free, and does
     * help on the rare setup where CLI and web genuinely share a pool — but
     * the execute() warning below is the part that actually matters:
     * restarting the web server is the real fix once ApcuCache is active.
     */
    private function clearApcuCache(): bool
    {
        if (!extension_loaded('apcu') || !function_exists('apcu_clear_cache')) {
            return false;
        }
        apcu_clear_cache();
        return true;
    }
}
