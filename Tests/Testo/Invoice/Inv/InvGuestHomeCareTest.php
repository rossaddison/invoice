<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\Inv;

use App\Infrastructure\Persistence\CategorySecondary\CategorySecondary;
use App\Infrastructure\Persistence\HomeCareRunDay\HomeCareRunDay;
use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\CategorySecondary\CategorySecondaryRepository;
use App\Invoice\Dwelling\DwellingRepository;
use App\Invoice\HomeCareSchedule\RunCalendarService;
use App\Invoice\Inv\GuestPullPointOrder;
use App\Invoice\Inv\HomeCareCrew;
use App\Invoice\Inv\InvGuestHomeCare;
use App\Invoice\Inv\WorkerInvAccess;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository;
use Mockery as m;
use Testo\Assert;
use Testo\Test;
use Yiisoft\Data\Reader\Iterable\IterableDataReader;

/**
 * What inv/guest asks of its HomeCare collaborators: the active workers by name, today's crew and
 * mode, the lead, today's run for the Start button, and the pull-point order of the grid.
 */
#[Test]
final class InvGuestHomeCareTest
{
    private function homeCare(
        ?HomeCareRunDay $today = null,
        ?CategorySecondary $run = null,
        ?GuestPullPointOrder $order = null,
    ): InvGuestHomeCare {
        /** @var DwellingRepository<\App\Infrastructure\Persistence\Dwelling\Dwelling>&m\MockInterface $dwellings */
        $dwellings = m::mock(DwellingRepository::class);
        /** @var InvWorkerAllocationService&m\MockInterface $allocations */
        $allocations = m::mock(InvWorkerAllocationService::class);
        /** @var WorkerInvAccess&m\MockInterface $access */
        $access = m::mock(WorkerInvAccess::class);
        $access->shouldReceive('leadWorkerId')->andReturn(3);
        $dave = new Worker('Dave', 'Smith', true);
        $dave->setId(3);
        $sam = new Worker('Sam', 'Jones', true);
        $sam->setId(4);
        /** @var WorkerRepository&m\MockInterface $workers */
        $workers = m::mock(WorkerRepository::class);
        $workers->shouldReceive('findAllActive')->andReturn([$dave, $sam]);
        /** @var HomeCareCrew&m\MockInterface $crew */
        $crew = m::mock(HomeCareCrew::class);
        $crew->shouldReceive('workerIds')->andReturn([3, 4]);
        $crew->shouldReceive('frontBack')->andReturn(true);
        /** @var RunCalendarService&m\MockInterface $calendar */
        $calendar = m::mock(RunCalendarService::class);
        $calendar->shouldReceive('forDate')->with(date('Y-m-d'))->andReturn($today);
        /** @var CategorySecondaryRepository&m\MockInterface $runs */
        $runs = m::mock(CategorySecondaryRepository::class);
        $runs->shouldReceive('repoCategorySecondaryQuery')->andReturn($run);
        /** @var GuestPullPointOrder&m\MockInterface $pullPointOrder */
        $pullPointOrder = $order ?? m::mock(GuestPullPointOrder::class);

        return new InvGuestHomeCare($dwellings, $allocations, $access, $workers, $crew, $calendar, $runs, $pullPointOrder);
    }

    public function activeWorkersAreNamedByFirstAndLastName(): void
    {
        Assert::same($this->homeCare()->workerNames(), [3 => 'Dave Smith', 4 => 'Sam Jones']);
    }

    public function theCrewTheModeAndTheLeadComeFromTheirCollaborators(): void
    {
        $homeCare = $this->homeCare();

        Assert::same($homeCare->crewIds(), [3, 4]);
        Assert::true($homeCare->crewFrontBack());
        Assert::same($homeCare->leadWorkerId(), 3);
    }

    public function todaysRunIsItsNameAndStartingPostcode(): void
    {
        $run = new CategorySecondary(null, 3, 'North 1');
        $run->setId(7);
        $run->setStartPostcode('AB1 2CD');

        Assert::same(
            $this->homeCare(new HomeCareRunDay(date('Y-m-d'), 7), $run)->todayRun(),
            ['name' => 'North 1', 'postcode' => 'AB1 2CD'],
        );
    }

    public function aRunWithNoStartPostcodeHasNullForIt(): void
    {
        $run = new CategorySecondary(null, 3, 'North 1');
        $run->setId(7);

        Assert::same(
            $this->homeCare(new HomeCareRunDay(date('Y-m-d'), 7), $run)->todayRun(),
            ['name' => 'North 1', 'postcode' => null],
        );
    }

    public function noRunTodayOrAVanishedRunIsNull(): void
    {
        Assert::null($this->homeCare(null, null)->todayRun());
        Assert::null($this->homeCare(new HomeCareRunDay(date('Y-m-d'), 7), null)->todayRun());
    }

    public function theGridOrderIsDelegatedToThePullPointOrder(): void
    {
        $inv = new Inv();
        $inv->setId(1);
        $reader = new IterableDataReader([$inv]);
        /** @var GuestPullPointOrder&m\MockInterface $order */
        $order = m::mock(GuestPullPointOrder::class);
        $order->shouldReceive('order')->once()->with([$inv])->andReturn($reader);

        Assert::same($this->homeCare(null, null, $order)->byPullPoint([$inv]), $reader);
    }
}
