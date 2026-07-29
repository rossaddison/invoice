<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Preset reasons a HomeCare field worker can pick from inv/guest to flag
// an invoice as do-not-send. Stored as a plain string column on Inv
// (do_not_send_reason), not as a native enum column — same convention as
// ProductType/StoreCoveTaxType. Deliberately a closed dropdown, no free
// text, per the confirmed design.

enum DoNotSendReason: string
{
    case PropertyInaccessible = 'property_inaccessible';
    case JobIncomplete = 'job_incomplete';
    case CustomerDispute = 'customer_dispute';
    case DamageOccurred = 'damage_occurred';
    case SafetyConcern = 'safety_concern';
    case Other = 'other';
}
