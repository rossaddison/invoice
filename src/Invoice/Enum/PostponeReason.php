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
}
