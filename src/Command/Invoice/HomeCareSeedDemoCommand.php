<?php

declare(strict_types=1);

namespace App\Command\Invoice;

use App\Invoice\HomeCareDemo\HomeCareDemoSeeder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Security\Random;
use Yiisoft\Yii\Console\ExitCode;

/**
 * TEST-SUPPORT TOOL -- not for production use. See HomeCareDemoSeeder.
 *
 *   php yii homecare/seed-demo            fresh demo data (replaces any earlier copy)
 *   php yii homecare/seed-demo --cleanup  remove the demo data
 */
final class HomeCareSeedDemoCommand extends Command
{
    protected static string $defaultName = 'homecare/seed-demo';

    public function __construct(private readonly HomeCareDemoSeeder $seeder)
    {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Seed (or with --cleanup, remove) an end-to-end HomeCare demo world for manual testing')
            ->addOption('cleanup', null, InputOption::VALUE_NONE, 'Only remove the demo data');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('cleanup')) {
            $removed = $this->seeder->cleanup();
            $summary = [];
            foreach ($removed as $table => $count) {
                $summary[] = $table . ' ' . $count;
            }
            $io->success('Demo data removed: ' . ([] === $summary ? 'nothing to remove' : implode(', ', $summary)));
            return ExitCode::OK;
        }
        // a fresh random secret every run, shown once here: nothing is hardcoded in the repo
        $loginSecret = Random::string(20);
        $logins = $this->seeder->seed($loginSecret);
        $io->success('HomeCare demo data created. Password for every demo login (this run only): ' . $loginSecret);
        $io->table(['Login', 'Role', 'What to try'], array_map(static fn (array $l): array => [$l['login'], $l['role'], $l['note']], $logins));
        $io->writeln([
            'Houses (drafts, one invoice each, street "' . HomeCareDemoSeeder::MARKER . ' Demo Street"):',
            '  House 1, 2  unallocated            -> allocate with the chips',
            '  House 3     whole house, Dave (open)',
            '  House 4     split: front Dave / back Sam (both open)',
            '  House 5     whole house, Sam, completed  -> sendable',
            '  House 6     front Dave completed / back Sam postponed (gate locked) -> sendable',
            'Crew is set to Lee, Dave and Sam; the lead is Lee. Remove it all with --cleanup.',
        ]);
        return ExitCode::OK;
    }
}
