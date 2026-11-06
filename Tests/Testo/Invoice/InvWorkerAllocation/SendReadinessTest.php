<?php

declare(strict_types=1);

namespace Tests\Testo\Invoice\InvWorkerAllocation;

use App\Infrastructure\Persistence\InvWorkerAllocation\InvWorkerAllocation;
use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;
use App\Invoice\InvWorkerAllocation\SendReadiness;
use Testo\Assert;
use Testo\Test;

/**
 * "A draft is sent only once the job is done" -- including the fronts-only
 * case where the side gate was locked and the back was postponed.
 */
#[Test]
final class SendReadinessTest
{
    private function side(AllocationSide $side, ?AllocationStatus $decision): InvWorkerAllocation
    {
        $allocation = new InvWorkerAllocation(12, $side->value, 7);
        null !== $decision && $allocation->decide($decision, 7, new \DateTimeImmutable());
        return $allocation;
    }

    public function anInvoiceWithNoAllocationsIsNotGated(): void
    {
        Assert::false(SendReadiness::blocksSending([]));
    }

    public function aWholeHouseStillAllocatedBlocks(): void
    {
        Assert::true(SendReadiness::blocksSending([$this->side(AllocationSide::Both, null)]));
    }

    public function aCompletedWholeHouseIsSendable(): void
    {
        Assert::false(SendReadiness::blocksSending([$this->side(AllocationSide::Both, AllocationStatus::Completed)]));
    }

    public function oneSplitSideStillOpenBlocksEvenIfTheOtherIsCompleted(): void
    {
        Assert::true(SendReadiness::blocksSending([
            $this->side(AllocationSide::Front, AllocationStatus::Completed),
            $this->side(AllocationSide::Back, null),
        ]));
    }

    public function frontsDoneAndBackPostponedIsSendable(): void
    {
        Assert::false(SendReadiness::blocksSending([
            $this->side(AllocationSide::Front, AllocationStatus::Completed),
            $this->side(AllocationSide::Back, AllocationStatus::Postponed),
        ]));
    }

    public function frontsDoneAndBackCancelledIsSendable(): void
    {
        Assert::false(SendReadiness::blocksSending([
            $this->side(AllocationSide::Front, AllocationStatus::Completed),
            $this->side(AllocationSide::Back, AllocationStatus::Cancelled),
        ]));
    }

    public function everySidePostponedOrCancelledBlocksBecauseNothingWasDone(): void
    {
        Assert::true(SendReadiness::blocksSending([$this->side(AllocationSide::Both, AllocationStatus::Postponed)]));
        Assert::true(SendReadiness::blocksSending([$this->side(AllocationSide::Both, AllocationStatus::Cancelled)]));
        Assert::true(SendReadiness::blocksSending([
            $this->side(AllocationSide::Front, AllocationStatus::Postponed),
            $this->side(AllocationSide::Back, AllocationStatus::Cancelled),
        ]));
    }
}
