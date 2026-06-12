<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object representing an ebMS3 error signal.
 *
 * An error signal is returned when the receiving AS4 access point rejects
 * or cannot process the inbound UserMessage. The errorCode follows the
 * ebMS3 error code catalogue (e.g. "EBMS:0001", "EBMS:0301").
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4ErrorSignal
{
    public function __construct(
        /** eb:MessageId of this signal message */
        public string $messageId,
        /** eb:RefToMessageId — references the original sent message */
        public string $refToMessageId,
        /** eb:Timestamp of the signal */
        public \DateTimeImmutable $timestamp,
        /** @error category attribute */
        public As4ErrorCategory $category,
        /** ebMS3 error code, e.g. "EBMS:0001" */
        public string $errorCode,
        public As4ErrorSeverity $severity,
        /** Brief human-readable description from @shortDescription attribute */
        public string $shortDescription,
        /** Detailed description from eb:Description child element */
        public string $description,
    ) {}

    public function isFailure(): bool
    {
        return $this->severity === As4ErrorSeverity::Failure;
    }
}
