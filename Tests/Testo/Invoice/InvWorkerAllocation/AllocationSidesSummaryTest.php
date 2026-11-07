<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Infrastructure\Persistence\Worker\Worker;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\Inv\RunSheetPdfRow;
use App\Invoice\Inv\RunSheetPdfRowBuilder;
use App\Invoice\InvWorkerAllocation\AllocationSidesSummary;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationService;
use App\Invoice\Worker\WorkerRepository;
use DateTimeImmutable;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Which side of a house is whose, for the run sheet PDF, the run sheet CSV and the offline snapshot.
 */
#[Test]
final class AllocationSidesSummaryTest
{
    private const int INV = 12;

    private function worker(int $id, string $first, string $last): Worker
    {
        $worker = new Worker($first, $last);
        $worker->setId($id);
        return $worker;
    }

    private function row(AllocationSide $side, int $workerId, ?AllocationStatus $decided = null, bool $carded = false): InvWorkerAllocation
    {
        $row = new InvWorkerAllocation(self::INV, $side->value, $workerId);
        null !== $decided && $row->decide($decided, $workerId, new DateTimeImmutable());
        $carded && $row->markCarded(new DateTimeImmutable());
        return $row;
    }

    /**
     * @param list<InvWorkerAllocation> $rows
     */
    private function summary(array $rows): AllocationSidesSummary
    {
        /** @var InvWorkerAllocationService&m\MockInterface $allocations */
        $allocations = m::mock(InvWorkerAllocationService::class);
        $allocations->shouldReceive('forInv')->with(self::INV)->andReturn($rows);
        $allocations->shouldReceive('forInv')->andReturn([]);
        /** @var WorkerRepository&m\MockInterface $workers */
        $workers = m::mock(WorkerRepository::class);
        $workers->shouldReceive('findAllActive')->andReturn([$this->worker(7, 'Dave', 'Smith'), $this->worker(8, 'Sam', 'Jones')]);
        return new AllocationSidesSummary($allocations, $workers);
    }

    /** The words a translated surface would use. */
    private function sideWord(AllocationSide $side): string
    {
        return 'T-' . $side->value;
    }

    private function statusWord(AllocationStatus $status): string
    {
        return 'S-' . $status->value;
    }

    public function activeWorkersAreNamedByIdOnce(): void
    {
        Assert::same($this->summary([])->workerNames(), [7 => 'Dave Smith', 8 => 'Sam Jones']);
    }

    public function eachAllocatedSideIsReportedWithItsWorkerStatusAndCard(): void
    {
        $summary = $this->summary([
            $this->row(AllocationSide::Front, 7, AllocationStatus::Completed, true),
            $this->row(AllocationSide::Back, 8),
        ]);

        $sides = $summary->forInv(self::INV, $summary->workerNames());

        Assert::same(count($sides), 2);
        Assert::same($sides[0]->side, AllocationSide::Front);
        Assert::same($sides[0]->workerName, 'Dave Smith');
        Assert::same($sides[0]->workerId, 7);
        Assert::same($sides[0]->status, AllocationStatus::Completed);
        Assert::true($sides[0]->carded);
        Assert::same($sides[1]->workerName, 'Sam Jones');
        Assert::false($sides[1]->carded);
    }

    public function aWorkerWhoIsNoLongerActiveShowsTheirIdNotABlank(): void
    {
        $summary = $this->summary([$this->row(AllocationSide::Both, 99)]);

        Assert::same($summary->forInv(self::INV, [])[0]->workerName, '#99');
    }

    public function aHouseWithNoAllocationReadsAsNothing(): void
    {
        $summary = $this->summary([]);

        Assert::same($summary->forInv(self::INV, []), []);
        Assert::same($summary->describe(self::INV, [], $this->sideWord(...), $this->statusWord(...)), '');
    }

    public function theLineNamesEachSideAndAddsTheStatusOnlyOnceItIsSettled(): void
    {
        $summary = $this->summary([
            $this->row(AllocationSide::Front, 7, AllocationStatus::Completed),
            $this->row(AllocationSide::Back, 8),
        ]);

        Assert::same(
            $summary->describe(self::INV, $summary->workerNames(), $this->sideWord(...), $this->statusWord(...)),
            'T-front: Dave Smith (S-completed); T-back: Sam Jones',
        );
    }

    public function plainEnglishLabelsSuitAnExportWithNoTranslator(): void
    {
        Assert::same(AllocationSide::Both->plainLabel(), 'Whole house');
        Assert::same(AllocationSide::Front->plainLabel(), 'Front');
        Assert::same(AllocationSide::Back->plainLabel(), 'Back');
    }

    public function theRunSheetPdfRowsGetTheirOwnHousesSidesAndOthersKeepNone(): void
    {
        $summary = $this->summary([$this->row(AllocationSide::Front, 7)]);
        $withSides = new RunSheetPdfRow('1 High St', null, 'Jane', null, 'INV-12', null, null, self::INV);
        $other = new RunSheetPdfRow('2 High St', null, 'Joe', null, 'INV-13', null, null, 13);

        $rows = (new RunSheetPdfRowBuilder())->withSides([$withSides, $other], $summary, $this->sideWord(...), $this->statusWord(...));

        Assert::same($rows[0]->sides, 'T-front: Dave Smith');
        Assert::same($rows[1]->sides, '');
        // everything else on the row is untouched
        Assert::same($rows[0]->address, '1 High St');
        Assert::same($rows[0]->invId, self::INV);
        Assert::same($rows[0]->invNumber, 'INV-12');
    }
}
