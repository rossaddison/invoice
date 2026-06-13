<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Typed state machine for the AS4 message lifecycle.
 *
 * Transitions per eDelivery AS4 2.0 section 3.3.2:
 *
 *   Pending  → Sent            HTTP transport succeeded; AS4 receipt not yet received
 *   Sent     → ReceiptReceived Receipt signal confirmed delivery at protocol level
 *   Sent     → Failed          Timeout (EBMS:0301) or max retries exceeded
 *   Pending  → Duplicate       Receiver detected an identical MessageId
 *   ReceiptReceived → Delivered Business-level confirmation processed
 *
 * The string values must match the existing DB column values in as4_messages.state.
 */
enum As4MessageState: string
{
    case pending         = 'pending';
    /** HTTP transport succeeded; AS4 receipt signal not yet received. */
    case sent            = 'sent';
    case receiptReceived = 'receipt';
    case failed          = 'failed';
    case duplicate       = 'duplicate';
    case delivered       = 'delivered';
}
