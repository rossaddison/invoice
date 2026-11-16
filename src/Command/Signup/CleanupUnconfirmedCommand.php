<?php

declare(strict_types=1);

namespace App\Command\Signup;

use App\Auth\UnconfirmedSignupCleaner;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

/**
 *   php yii signup/cleanup-unconfirmed                       list what would be removed (nothing is deleted)
 *   php yii signup/cleanup-unconfirmed --force               remove it
 *   php yii signup/cleanup-unconfirmed --older-than-hours=72 only signups older than 72 hours (default 24)
 *
 * See UnconfirmedSignupCleaner for exactly which accounts qualify.
 */
final class CleanupUnconfirmedCommand extends Command
{
    protected static string $defaultName = 'signup/cleanup-unconfirmed';

    private const string OPTION_FORCE = 'force';
    private const string OPTION_HOURS = 'older-than-hours';
    private const int DEFAULT_HOURS = 24;

    public function __construct(private readonly UnconfirmedSignupCleaner $cleaner)
    {
        parent::__construct();
    }

    #[\Override]
    public function configure(): void
    {
        $this
            ->setDescription('Remove public signups whose confirmation link was never clicked (dry run unless --force)')
            ->addOption(self::OPTION_FORCE, null, InputOption::VALUE_NONE, 'Actually delete; without it only lists')
            ->addOption(
                self::OPTION_HOURS,
                null,
                InputOption::VALUE_REQUIRED,
                'Only signups whose link was issued more than this many hours ago',
                (string) self::DEFAULT_HOURS,
            );
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $hours = $this->hours($input);
        $minimumHours = (int) ceil(UnconfirmedSignupCleaner::LINK_LIFETIME_SECONDS / 3600);
        if ($hours === null || $hours < $minimumHours) {
            $io->error('--' . self::OPTION_HOURS . ' must be a whole number of at least ' . $minimumHours
                . ': a younger signup may still be about to confirm.');
            return ExitCode::USAGE;
        }

        $candidates = $this->cleaner->findCandidates(new DateTimeImmutable('-' . $hours . ' hours'));
        if ([] === $candidates) {
            $io->success('No unconfirmed signups older than ' . $hours . ' hours.');
            return ExitCode::OK;
        }

        $io->table(['User id', 'Login', 'Signed up'], array_map(
            static fn (array $c): array => [$c['userId'], $c['login'], $c['createdAt']],
            $candidates,
        ));

        if (!$input->getOption(self::OPTION_FORCE)) {
            $io->note(count($candidates) . ' unconfirmed signup(s) would be removed. Run again with --force to delete them.');
            return ExitCode::OK;
        }

        return $this->removeAll($io, $candidates);
    }

    private function hours(InputInterface $input): ?int
    {
        /** @var mixed $value */
        $value = $input->getOption(self::OPTION_HOURS);
        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    /** @param list<array{userId: int, login: string, createdAt: string}> $candidates */
    private function removeAll(SymfonyStyle $io, array $candidates): int
    {
        $removed = 0;
        $failed = 0;
        foreach ($candidates as $candidate) {
            try {
                $this->cleaner->remove($candidate['userId']);
                ++$removed;
            } catch (\Throwable $e) {
                ++$failed;
                $io->warning('User ' . $candidate['userId'] . ' was not removed: ' . $e->getMessage());
            }
        }
        $io->success($removed . ' unconfirmed signup(s) removed.');
        if ($failed > 0) {
            $io->error($failed . ' could not be removed and were left exactly as they were.');
            return ExitCode::UNSPECIFIED_ERROR;
        }
        return ExitCode::OK;
    }
}
