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

        $cacheDir = $this->aliases->get('@runtime/cache');

        if (!is_dir($cacheDir)) {
            $io->warning("Cache directory not found: {$cacheDir}");
            return ExitCode::OK;
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

        $io->success("Cache cleared — {$deleted} item(s) removed from {$cacheDir}");
        return ExitCode::OK;
    }
}
