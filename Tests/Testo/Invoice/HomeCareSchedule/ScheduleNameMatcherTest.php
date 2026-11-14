<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;
use App\Invoice\HomeCareSchedule\HomeCareAliasRepositoryInterface;
use App\Invoice\HomeCareSchedule\ScheduleNameMatcher;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Which real run or worker a name written in the schedule means.
 */
#[Test]
final class ScheduleNameMatcherTest
{
    /** @var array<int, string> */
    private const array RUNS = [
        1 => 'Bellshill 1', 2 => 'Bellshill 2', 3 => 'Bellshill F', 4 => 'Dunfermline 1', 5 => 'Dunfermline 2',
        6 => 'East Kilbride', 7 => 'Edinburgh', 8 => 'Bearsden', 9 => 'Milngavie',
    ];

    /** @var array<int, string> */
    private const array WORKERS = [11 => 'Andrew', 12 => 'Daniel', 13 => 'Ross', 14 => 'Callum'];

    /**
     * @param array<string, HomeCareAlias> $known "kind|alias" => the alias row already stored
     */
    private function matcher(array $known = []): ScheduleNameMatcher
    {
        /** @var HomeCareAliasRepositoryInterface&m\MockInterface $aliases */
        $aliases = m::mock(HomeCareAliasRepositoryInterface::class);
        $aliases->shouldReceive('repoLookupquery')->andReturnUsing(
            static fn (string $kind, string $alias): ?HomeCareAlias => $known[$kind . '|' . $alias] ?? null
        );
        return new ScheduleNameMatcher($aliases);
    }

    public function normalisingIgnoresCapitalsApostrophesFullStopsAndSpacing(): void
    {
        $matcher = $this->matcher();

        Assert::same($matcher->normalise("  Craig’s   Edinburgh "), 'craigs edinburgh');
        Assert::same($matcher->normalise('E. Kilbride'), 'e kilbride');
        Assert::same($matcher->normalise('Bellshill-1'), 'bellshill 1');
    }

    public function aNameThatIsTheSameOnceTidiedMatchesExactly(): void
    {
        $matcher = $this->matcher();

        Assert::same($matcher->matchRun('bellshill 1', self::RUNS), 1);
        Assert::same($matcher->matchRun('  BELLSHILL  F ', self::RUNS), 3);
        Assert::same($matcher->matchRun('Edinburgh', self::RUNS), 7);
    }

    public function anUnambiguousAbbreviationMatches(): void
    {
        $matcher = $this->matcher();

        Assert::same($matcher->matchRun('Dunf 2', self::RUNS), 5);
        Assert::same($matcher->matchRun('Dunfer 2', self::RUNS), 5);
        Assert::same($matcher->matchRun('E. Kilbride', self::RUNS), 6);
    }

    public function anAmbiguousNameIsLeftForAPersonToDecide(): void
    {
        $matcher = $this->matcher();

        // "Bellshill" alone could be 1, 2 or F; "Dunf" alone could be 1 or 2
        Assert::null($matcher->matchRun('Bellshill', self::RUNS));
        Assert::null($matcher->matchRun('Dunf', self::RUNS));
        // a different number is a different run, not a typo
        Assert::null($matcher->matchRun('Bellshill 3', self::RUNS));
    }

    public function anUnknownNameIsNeverGuessed(): void
    {
        $matcher = $this->matcher();

        Assert::null($matcher->matchRun("Craig's Edinburgh", self::RUNS));
        Assert::null($matcher->matchRun('Clydebank', self::RUNS));
        Assert::null($matcher->matchRun('', self::RUNS));
        // Milngavie and Bearsden are separate runs: neither matches the other
        Assert::same($matcher->matchRun('Milngavie', self::RUNS), 9);
        Assert::same($matcher->matchRun('Bearsden', self::RUNS), 8);
    }

    public function aMatchAPersonConfirmedBeforeWinsOverEverythingElse(): void
    {
        $known = ['run|craigs edinburgh' => new HomeCareAlias('run', 'craigs edinburgh', 7)];

        Assert::same($this->matcher($known)->matchRun("Craig's Edinburgh", self::RUNS), 7);
    }

    public function anAliasToARunThatNoLongerExistsFallsBackToTheOrdinaryMatching(): void
    {
        $known = ['run|edinburgh' => new HomeCareAlias('run', 'edinburgh', 99)];

        Assert::same($this->matcher($known)->matchRun('Edinburgh', self::RUNS), 7);
    }

    public function workersMatchByFirstNameAndByCommonShortForm(): void
    {
        $matcher = $this->matcher();

        Assert::same($matcher->matchWorker('Ross', self::WORKERS), 13);
        Assert::same($matcher->matchWorker('Callum', self::WORKERS), 14);
        Assert::same($matcher->matchWorker('Andy', self::WORKERS), 11);
        Assert::same($matcher->matchWorker('Dan', self::WORKERS), 12);
        Assert::same($matcher->matchWorker('Andrew', self::WORKERS), 11);
    }

    public function aShortFormThatCouldBeTwoPeopleIsNotGuessed(): void
    {
        $workers = [1 => 'Andrew', 2 => 'Andre'];

        Assert::null($this->matcher()->matchWorker('And', $workers));
        // "Sam" is a short form of Samuel only; Samantha is a different person
        Assert::null($this->matcher()->matchWorker('Sam', [1 => 'Samantha']));
    }

    public function aWorkerAliasAndARunAliasAreSeparateKinds(): void
    {
        $known = ['worker|andy' => new HomeCareAlias('worker', 'andy', 12)];

        Assert::same($this->matcher($known)->matchWorker('Andy', self::WORKERS), 12);
        Assert::null($this->matcher($known)->matchRun('Andy', self::RUNS));
    }

    public function rememberingAMatchStoresTheNormalisedNameOnce(): void
    {
        /** @var HomeCareAliasRepositoryInterface&m\MockInterface $aliases */
        $aliases = m::mock(HomeCareAliasRepositoryInterface::class);
        $aliases->shouldReceive('repoLookupquery')->with('run', 'craigs edinburgh')->andReturn(null);
        $saved = [];
        $aliases->shouldReceive('save')->once()->andReturnUsing(static function (HomeCareAlias $a) use (&$saved): void {
            $saved[] = [$a->getKind(), $a->getAlias(), $a->getTargetId()];
        });

        (new ScheduleNameMatcher($aliases))->remember('run', "Craig’s  Edinburgh", 7);

        Assert::same($saved, [['run', 'craigs edinburgh', 7]]);
    }

    public function rememberingTheSameMatchAgainChangesNothingAndANewTargetUpdatesIt(): void
    {
        $existing = new HomeCareAlias('run', 'dunf 2', 5);
        /** @var HomeCareAliasRepositoryInterface&m\MockInterface $aliases */
        $aliases = m::mock(HomeCareAliasRepositoryInterface::class);
        $aliases->shouldReceive('repoLookupquery')->with('run', 'dunf 2')->andReturn($existing);
        $aliases->shouldReceive('save')->once()->with($existing);
        $matcher = new ScheduleNameMatcher($aliases);

        $matcher->remember('run', 'Dunf 2', 5);   // already that: no save
        $matcher->remember('run', 'Dunf 2', 4);   // now means another run: updated and saved once

        Assert::same($existing->getTargetId(), 4);
    }

    public function rememberingABlankNameDoesNothing(): void
    {
        /** @var HomeCareAliasRepositoryInterface&m\MockInterface $aliases */
        $aliases = m::mock(HomeCareAliasRepositoryInterface::class);
        $aliases->shouldNotReceive('save');
        $aliases->shouldNotReceive('repoLookupquery');

        (new ScheduleNameMatcher($aliases))->remember('run', '  .. ', 3);
    }
}
