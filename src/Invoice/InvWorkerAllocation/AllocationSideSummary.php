<?php

declare(strict_types=1);

namespace App\Invoice\InvWorkerAllocation;

use App\Invoice\Enum\AllocationSide;
use App\Invoice\Enum\AllocationStatus;

/**
 * One side of one house as the run sheets and the offline snapshot show it: which side, who
 * holds it, and how it stands.
 */
final readonly class AllocationSideSummary
{
    public function __construct(
        public AllocationSide $side,
        public int $workerId,
        public string $workerName,
        public AllocationStatus $status,
        public bool $carded,
    ) {
    }

    /**
     * "Front: Dave Smith", or "Front: Dave Smith (Done)" once the side is settled. The words
     * come from the caller, so the PDF can translate them and the CSV can stay plain English.
     *
     * @param callable(AllocationSide): string $sideLabel
     * @param callable(AllocationStatus): string $statusLabel
     */
    public function text(callable $sideLabel, callable $statusLabel): string
    {
        $text = $sideLabel($this->side) . ': ' . $this->workerName;
        return $this->status->isDecision() ? $text . ' (' . $statusLabel($this->status) . ')' : $text;
    }
}
