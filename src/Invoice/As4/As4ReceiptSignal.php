<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object representing a successful ebMS3 receipt signal.
 *
 * A receipt is returned (synchronously or asynchronously) by the receiving
 * AS4 access point to confirm delivery of the original UserMessage.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4ReceiptSignal
{
    public function __construct(
        /** eb:MessageId of this signal message */
        public string $messageId,
        /** eb:RefToMessageId — references the original sent message */
        public string $refToMessageId,
        /** eb:Timestamp of the signal (ISO 8601) */
        public string $timestamp,
    ) {}
}
