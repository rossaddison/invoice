<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Which part of a house one InvWorkerAllocation covers. Stored as a plain
// string column (side), not a native enum column — same convention as
// DoNotSendReason/HomeCareRunSheetStatus.

enum AllocationSide: string
{
    // One worker does the whole house.
    case Both = 'both';
    // The fronts only — the backs are allocated separately.
    case Front = 'front';
    // The backs only — the fronts are allocated separately.
    case Back = 'back';

    /**
     * A house is either allocated whole (Both) or split into Front and Back,
     * never a mix; this is the side a given side cannot coexist with.
     *
     * @return list<self>
     */
    public function conflictsWith(): array
    {
        return match ($this) {
            self::Both => [self::Front, self::Back],
            self::Front, self::Back => [self::Both],
        };
    }

    /**
     * The Bootstrap Icons class for the side (door, tree, house): the picture carries the meaning.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Both => 'bi-house-door-fill',
            self::Front => 'bi-door-closed-fill',
            self::Back => 'bi-tree-fill',
        };
    }
}
