<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4DuplicateDetectorInterface
{
    /**
     * Returns true when a message with $messageId already exists in the store.
     *
     * Covers both inbound messages previously received (stored with state=received)
     * and outbound messages sent by this AP, preventing accidental reuse of MessageIds.
     * Satisfies eDelivery AS4 2.0 §3.3.5 duplicate-elimination requirement.
     */
    public function isDuplicate(string $messageId): bool;
}
