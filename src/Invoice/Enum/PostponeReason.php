<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Why an InvWorkerAllocation was postponed. Optional — stored as a plain
// string column (postpone_reason), not a native enum column, same
// convention as DoNotSendReason.

enum PostponeReason: string
{
    case GateLocked = 'gate_locked';
    case NoAccess = 'no_access';
    case CustomerAway = 'customer_away';
    case Other = 'other';

    /**
     * The Bootstrap Icons class for the reason, so it can be read without the words.
     */
    public function icon(): string
    {
        return match ($this) {
            self::GateLocked => 'bi-lock-fill',
            self::NoAccess => 'bi-shield-fill-x',
            self::CustomerAway => 'bi-airplane-fill',
            self::Other => 'bi-question-circle-fill',
        };
    }
}
