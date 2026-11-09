<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareAlias\HomeCareAlias;
use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use App\Invoice\HomeCareSchedule\HomeCareAliasRepositoryInterface;
use App\Invoice\HomeCareSchedule\HomeCareRunDayRepositoryInterface;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\HomeCareSchedule\RunScheduleParser;
use App\Invoice\HomeCareSchedule\ScheduleImporter;
use App\Invoice\HomeCareSchedule\ScheduleNameMatcher;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Pasted schedule -> proposed matches -> calendar days.
 */
#[Test]
final class ScheduleImporterTest
{
    private const string TEXT = "NOVEMBER\n2 - Bellshill 1 (Andy & Dan)\n3 - Dunf 2 (\" & \")\n4\n";

    /**
     * @param list<HomeCareRunDay> $saved filled with every day saved
     * @param list<HomeCareAlias> $aliases filled with every alias remembered
     */
    private function importer(array &$saved, array &$aliases): ScheduleImporter
    {
        /** @var HomeCareAliasRepositoryInterface&m\MockInterface $aliasRepo */
        $aliasRepo = m::mock(HomeCareAliasRepositoryInterface::class);
        $aliasRepo->shouldReceive('repoLookupquery')->andReturn(null);
        $aliasRepo->shouldReceive('save')->andReturnUsing(static function (HomeCareAlias $a) use (&$aliases): void {
            $aliases[] = $a;
        });
        /** @var HomeCareRunDayRepositoryInterface&m\MockInterface $dayRepo */
        $dayRepo = m::mock(HomeCareRunDayRepositoryInterface::class);
        $dayRepo->shouldReceive('repoDatequery')->andReturn(null);
        $dayRepo->shouldReceive('save')->andReturnUsing(static function (HomeCareRunDay $d) use (&$saved): void {
            $saved[] = $d;
        });
        return new ScheduleImporter(new ScheduleNameMatcher($aliasRepo), new RunCalendarService($dayRepo));
    }

    public function suggestMatchesWhatItIsCertainOfAndLeavesTheRestNull(): void
    {
        $saved = [];
        $aliases = [];
        $schedule = (new RunScheduleParser())->parse(self::TEXT, 2026, 11);

        $suggested = $this->importer($saved, $aliases)->suggest(
            $schedule,
            [5 => 'Bellshill 1', 6 => 'Dunfermline 2'],
            [11 => 'Andrew', 12 => 'Daniel']
        );

        Assert::same($suggested['runs']['Bellshill 1'], 5);
        Assert::same($suggested['runs']['Dunf 2'], 6);
        Assert::same($suggested['workers']['Andy'], 11);
        Assert::same($suggested['workers']['Dan'], 12);
    }

    public function saveWritesResolvedDaysAndRemembersTheNames(): void
    {
        $saved = [];
        $aliases = [];
        $schedule = (new RunScheduleParser())->parse(self::TEXT, 2026, 11);

        $result = $this->importer($saved, $aliases)->save(
            $schedule,
            ['Bellshill 1' => 5, 'Dunf 2' => 6],
            ['Andy' => 11, 'Dan' => 12]
        );

        Assert::same($result->created, 2);
        Assert::same($result->changed, 0);
        Assert::same($result->skippedDates, []);
        Assert::same(count($saved), 2);
        Assert::same($saved[0]->getRunDate(), '2026-11-02');
        Assert::same($saved[0]->getCategorySecondaryId(), 5);
        Assert::same($saved[0]->getCrewWorkerIds(), [11, 12]);
        Assert::same($saved[1]->getCrewWorkerIds(), [11, 12]);
        Assert::same(count($aliases), 4);
    }

    public function anUnchosenRunSkipsItsDaysAndAnUnchosenWorkerIsLeftOffTheCrew(): void
    {
        $saved = [];
        $aliases = [];
        $schedule = (new RunScheduleParser())->parse(self::TEXT, 2026, 11);

        $result = $this->importer($saved, $aliases)->save($schedule, ['Bellshill 1' => 5], ['Andy' => 11]);

        Assert::same($result->created, 1);
        Assert::same($result->skippedDates, ['2026-11-03']);
        Assert::same($result->droppedCrewNames, ['Dan']);
        Assert::same($saved[0]->getCrewWorkerIds(), [11]);
    }
}
