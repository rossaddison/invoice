<?php

declare(strict_types=1);

namespace Tests\Testo\Command;

use App\Auth\UnconfirmedSignupCleaner;
use App\Command\Signup\CleanupUnconfirmedCommand;
use RuntimeException;
use Symfony\Component\Console\Tester\CommandTester;
use Testo\Assert;
use Testo\Test;
use Tests\Testo\Support\TypedMock;
use Yiisoft\Yii\Console\ExitCode;

/**
 * `php yii signup/cleanup-unconfirmed`: a dry run unless --force, nothing younger than the confirmation link's
 * lifetime, and a removal that fails for one account leaves that account whole and carries on.
 *
 * @see CleanupUnconfirmedCommand
 */
#[Test]
final class CleanupUnconfirmedCommandTest
{
    /** @var list<array{userId: int, login: string, createdAt: string}> */
    private const array CANDIDATES = [
        ['userId' => 5, 'login' => 'virtualbot01', 'createdAt' => '2026-11-01 10:00:00'],
        ['userId' => 8, 'login' => 'virtualbot02', 'createdAt' => '2026-11-01 11:00:00'],
    ];

    /**
     * @param list<array{userId: int, login: string, createdAt: string}> $candidates
     */
    /**
     * The command's output with line breaks collapsed. Symfony wraps output to the terminal width, which is
     * narrower on CI than on a developer machine, so a sentence can be split across two lines.
     */
    private function display(CommandTester $tester): string
    {
        return (string) preg_replace('/\s+/', ' ', $tester->getDisplay());
    }

    private function tester(array $candidates, ?UnconfirmedSignupCleaner $cleaner = null): CommandTester
    {
        // SymfonyStyle wraps its blocks to the terminal width (and prefixes every wrapped line), which is much
        // narrower on CI than on a developer machine and split the sentences these tests look for. A fixed wide
        // terminal makes the output the same everywhere.
        putenv('COLUMNS=200');
        if (null === $cleaner) {
            $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
            $cleaner->shouldReceive('findCandidates')->andReturn($candidates);
        }
        return new CommandTester(new CleanupUnconfirmedCommand($cleaner));
    }

    public function withNothingToRemoveItSaysSoAndSucceeds(): void
    {
        $tester = $this->tester([]);

        Assert::same($tester->execute([]), ExitCode::OK);
        Assert::true(str_contains($this->display($tester), 'No unconfirmed signups older than 24 hours.'));
    }

    public function withoutForceItOnlyListsAndRemovesNothing(): void
    {
        $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
        $cleaner->shouldReceive('findCandidates')->andReturn(self::CANDIDATES);
        $cleaner->shouldReceive('remove')->never();
        $tester = $this->tester([], $cleaner);

        Assert::same($tester->execute([]), ExitCode::OK);
        $display = $this->display($tester);
        Assert::true(str_contains($display, 'virtualbot01'));
        Assert::true(str_contains($display, 'virtualbot02'));
        Assert::true(str_contains($display, '2 unconfirmed signup(s) would be removed. Run again with --force'));
    }

    public function withForceItRemovesEveryCandidate(): void
    {
        $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
        $cleaner->shouldReceive('findCandidates')->andReturn(self::CANDIDATES);
        $cleaner->shouldReceive('remove')->once()->with(5);
        $cleaner->shouldReceive('remove')->once()->with(8);
        $tester = $this->tester([], $cleaner);

        Assert::same($tester->execute(['--force' => true]), ExitCode::OK);
        Assert::true(str_contains($this->display($tester), '2 unconfirmed signup(s) removed.'));
    }

    public function aRemovalThatFailsLeavesThatAccountAndCarriesOnWithTheError(): void
    {
        $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
        $cleaner->shouldReceive('findCandidates')->andReturn(self::CANDIDATES);
        $cleaner->shouldReceive('remove')->once()->with(5)->andThrow(new RuntimeException('constraint'));
        $cleaner->shouldReceive('remove')->once()->with(8);
        $tester = $this->tester([], $cleaner);

        Assert::same($tester->execute(['--force' => true]), ExitCode::UNSPECIFIED_ERROR);
        $display = $this->display($tester);
        Assert::true(str_contains($display, 'User 5 was not removed: constraint'));
        Assert::true(str_contains($display, '1 unconfirmed signup(s) removed.'));
        Assert::true(str_contains($display, '1 could not be removed'));
    }

    public function anHoursValueBelowTheLinkLifetimeIsRefusedBeforeAnythingIsLookedUp(): void
    {
        $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
        $cleaner->shouldReceive('findCandidates')->never();
        $tester = $this->tester([], $cleaner);

        Assert::same($tester->execute(['--older-than-hours' => '0']), ExitCode::USAGE);
        Assert::true(str_contains($this->display($tester), 'a younger signup may still be about to confirm'));
    }

    public function anHoursValueThatIsNotAWholeNumberIsRefused(): void
    {
        foreach (['abc', '-3', '1.5', ''] as $hours) {
            $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
            $cleaner->shouldReceive('findCandidates')->never();

            Assert::same($this->tester([], $cleaner)->execute(['--older-than-hours' => $hours]), ExitCode::USAGE, $hours);
        }
    }

    public function theHoursValueIsPassedOnAsTheCutoff(): void
    {
        $cutoffs = [];
        $cleaner = TypedMock::of(UnconfirmedSignupCleaner::class);
        $cleaner->shouldReceive('findCandidates')->andReturnUsing(function (\DateTimeImmutable $cutoff) use (&$cutoffs): array {
            $cutoffs[] = $cutoff;
            return [];
        });

        $this->tester([], $cleaner)->execute(['--older-than-hours' => '72']);

        Assert::same(count($cutoffs), 1);
        // about 72 hours ago, give or take the seconds the test itself takes
        Assert::true(abs((time() - $cutoffs[0]->getTimestamp()) - 72 * 3600) < 60);
    }
}
