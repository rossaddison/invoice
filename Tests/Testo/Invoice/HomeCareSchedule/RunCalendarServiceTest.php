<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\HomeCareSchedule;

use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use App\Invoice\HomeCareSchedule\HomeCareRunDayRepositoryInterface;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * The run calendar: which run is cleaned on a date, and by which crew.
 */
#[Test]
final class RunCalendarServiceTest
{
    private function repository(): HomeCareRunDayRepositoryInterface&m\MockInterface
    {
        /** @var HomeCareRunDayRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(HomeCareRunDayRepositoryInterface::class);
        return $repository;
    }

    public function aNewDayIsSavedWithItsRunAndCrewInOrder(): void
    {
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->with('2026-11-02')->andReturn(null);
        $saved = null;
        $repository->shouldReceive('save')->once()->andReturnUsing(static function (HomeCareRunDay $d) use (&$saved): void {
            $saved = $d;
        });

        $new = (new RunCalendarService($repository))->schedule('2026-11-02', 5, [13, 12, 11], 'bad weather swap');

        Assert::true($new);
        Assert::same($saved?->getRunDate(), '2026-11-02');
        Assert::same($saved?->getCategorySecondaryId(), 5);
        Assert::same($saved?->getCrewWorkerIds(), [13, 12, 11]);
        Assert::same($saved?->getNotes(), 'bad weather swap');
    }

    public function schedulingAnExistingDateChangesItInsteadOfAddingASecond(): void
    {
        $existing = new HomeCareRunDay('2026-11-02', 5, '13,12');
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->with('2026-11-02')->andReturn($existing);
        $repository->shouldReceive('save')->once()->with($existing);

        $new = (new RunCalendarService($repository))->schedule('2026-11-02', 6, [11]);

        Assert::false($new);
        Assert::same($existing->getCategorySecondaryId(), 6);
        Assert::same($existing->getCrewWorkerIds(), [11]);
    }

    public function aCrewIsCutToThreeDistinctWorkersAndBlankIdsAreDropped(): void
    {
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->andReturn(null);
        $saved = null;
        $repository->shouldReceive('save')->andReturnUsing(static function (HomeCareRunDay $d) use (&$saved): void {
            $saved = $d;
        });

        (new RunCalendarService($repository))->schedule('2026-11-02', 5, [13, 0, 13, 12, 11, 14]);

        Assert::same($saved?->getCrewWorkerIds(), [13, 12, 11]);
    }

    public function aDayWithNoCrewWrittenKeepsNoCrew(): void
    {
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->andReturn(null);
        $saved = null;
        $repository->shouldReceive('save')->andReturnUsing(static function (HomeCareRunDay $d) use (&$saved): void {
            $saved = $d;
        });

        (new RunCalendarService($repository))->schedule('2026-11-02', 5, []);

        Assert::same($saved?->getCrewWorkerIds(), []);
    }

    public function aMonthIsTheRangeFromItsFirstDayToTheNextMonthsFirst(): void
    {
        $repository = $this->repository();
        $day = new HomeCareRunDay('2026-12-01', 3);
        $repository->shouldReceive('repoRangequery')->once()->with('2026-12-01', '2027-01-01')->andReturn([$day]);

        Assert::same((new RunCalendarService($repository))->forMonth(2026, 12), [$day]);
    }

    public function clearingADeletesItAndClearingAnOpenDayDoesNothing(): void
    {
        $day = new HomeCareRunDay('2026-11-03', 3);
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->with('2026-11-03')->andReturn($day);
        $repository->shouldReceive('repoDatequery')->with('2026-11-04')->andReturn(null);
        $repository->shouldReceive('delete')->once()->with($day);
        $service = new RunCalendarService($repository);

        $service->clear('2026-11-03');
        $service->clear('2026-11-04');
    }

    public function theCrewForADateIsEmptyWhenThereIsNoRun(): void
    {
        $repository = $this->repository();
        $repository->shouldReceive('repoDatequery')->with('2026-11-02')->andReturn(new HomeCareRunDay('2026-11-02', 5, '13,12'));
        $repository->shouldReceive('repoDatequery')->with('2026-11-04')->andReturn(null);
        $service = new RunCalendarService($repository);

        Assert::same($service->crewFor('2026-11-02'), [13, 12]);
        Assert::same($service->crewFor('2026-11-04'), []);
    }

    public function theCrewIdsOfAStoredDayReadBackCleanlyEvenIfMessy(): void
    {
        $day = new HomeCareRunDay('2026-11-02', 5, ' 13, 12 ,,13,x,0');

        Assert::same($day->getCrewWorkerIds(), [13, 12]);
    }
}
