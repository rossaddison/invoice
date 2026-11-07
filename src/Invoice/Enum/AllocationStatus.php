<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Where one InvWorkerAllocation stands. Stored as a plain string column
// (status), not a native enum column — same convention as DoNotSendReason.

enum AllocationStatus: string
{
    // Allocated to a worker; nothing has been decided yet.
    case Allocated = 'allocated';
    // The side was cleaned.
    case Completed = 'completed';
    // Not done this visit (e.g. the side gate was locked); carries over to
    // the next run.
    case Postponed = 'postponed';
    // Will not be done.
    case Cancelled = 'cancelled';

    /**
     * Completed, postponed and cancelled are the three ways a worker (or the
     * lead) can settle an allocation; Allocated is only the starting state.
     */
    public function isDecision(): bool
    {
        return $this !== self::Allocated;
    }

    /**
     * The Bootstrap Icons class for the status. Deliberately unlike the ✓ / pause / ✖ action buttons, so "this is done" is never mistaken for "mark it done".
     */
    public function icon(): string
    {
        return match ($this) {
            self::Allocated => 'bi-hourglass-split',
            self::Completed => 'bi-flag-fill',
            self::Postponed => 'bi-clock-fill',
            self::Cancelled => 'bi-x-octagon-fill',
        };
    }
}
