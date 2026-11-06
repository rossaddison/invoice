<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\InvWorkerAllocation\AllocationSendGate;
use App\Invoice\InvWorkerAllocation\InvWorkerAllocationRepositoryInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

/**
 * Keeps Inv::$allocation_blocks_sending in step with the invoice's
 * allocations -- and only ever gates a draft.
 */
#[Test]
final class AllocationSendGateTest
{
    private function inv(int $statusId): Inv
    {
        $inv = new Inv(status_id: $statusId);
        $inv->setId(12);
        return $inv;
    }

    private function side(AllocationSide $side, ?AllocationStatus $decision): InvWorkerAllocation
    {
        $allocation = new InvWorkerAllocation(12, $side->value, 7);
        null !== $decision && $allocation->decide($decision, 7, new \DateTimeImmutable());
        return $allocation;
    }

    /**
     * @param list<InvWorkerAllocation> $allocations
     */
    private function gate(array $allocations): AllocationSendGate
    {
        /** @var InvWorkerAllocationRepositoryInterface&m\MockInterface $repository */
        $repository = m::mock(InvWorkerAllocationRepositoryInterface::class);
        $repository->shouldReceive('repoForInvquery')->with(12)->andReturn($allocations);
        return new AllocationSendGate($repository);
    }

    public function aDraftWithAnOpenSideIsBlockedAndTheFlagIsSetOnTheInvoice(): void
    {
        $inv = $this->inv(1);

        $blocked = $this->gate([$this->side(AllocationSide::Both, null)])->apply($inv);

        Assert::true($blocked);
        Assert::true($inv->getAllocationBlocksSending());
        Assert::true($inv->blocksSending());
    }

    public function completingTheJobClearsTheBlock(): void
    {
        $inv = $this->inv(1);
        $inv->setAllocationBlocksSending(true);

        $blocked = $this->gate([$this->side(AllocationSide::Both, AllocationStatus::Completed)])->apply($inv);

        Assert::false($blocked);
        Assert::false($inv->blocksSending());
    }

    public function frontsDoneAndBackPostponedIsNotBlocked(): void
    {
        $inv = $this->inv(1);

        $blocked = $this->gate([
            $this->side(AllocationSide::Front, AllocationStatus::Completed),
            $this->side(AllocationSide::Back, AllocationStatus::Postponed),
        ])->apply($inv);

        Assert::false($blocked);
    }

    public function aDraftWithNoAllocationsIsNeverGated(): void
    {
        $inv = $this->inv(1);
        $inv->setAllocationBlocksSending(true);

        Assert::false($this->gate([])->apply($inv));
        Assert::false($inv->blocksSending());
    }

    public function aSentInvoiceIsNeverGatedEvenWithOpenAllocations(): void
    {
        foreach ([2, 3, 4] as $statusId) {
            $inv = $this->inv($statusId);
            $inv->setAllocationBlocksSending(true);

            $blocked = $this->gate([$this->side(AllocationSide::Both, null)])->apply($inv);

            Assert::false($blocked);
            Assert::false($inv->blocksSending());
        }
    }

    public function theGateIsIndependentOfTheWorkersDoNotSendFlag(): void
    {
        $inv = $this->inv(1);
        $inv->setDoNotSend(true);

        $this->gate([$this->side(AllocationSide::Both, AllocationStatus::Completed)])->apply($inv);

        Assert::false($inv->getAllocationBlocksSending());
        Assert::true($inv->blocksSending());
    }
}
