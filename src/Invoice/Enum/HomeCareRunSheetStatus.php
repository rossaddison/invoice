<?php

declare(strict_types=1);

namespace App\Invoice\Enum;

// Lifecycle of one HomeCareRunSheet paper-signoff cycle. Stored as a plain
// string column on HomeCareRunSheet (status), not as a native enum column —
// same convention as DoNotSendReason/ProductType.

enum HomeCareRunSheetStatus: string
{
    // Spreadsheet generated and handed off for printing/inking; nothing else
    // has happened yet.
    case Exported = 'exported';
    // The inked sheet has been scanned back in and stored, but the AI vision
    // read hasn't run (or hasn't been confirmed) yet.
    case Scanned = 'scanned';
    // Vision extraction has produced per-row adjustments; the office is
    // reviewing the staging screen before anything touches Inv.
    case PendingReview = 'pending_review';
    // Confirmed adjustments have been applied to Inv and the whole run has
    // been marked sent — terminal state.
    case Applied = 'applied';
}
