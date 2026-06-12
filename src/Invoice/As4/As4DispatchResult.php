<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable value object returned by As4MessageDispatcher::dispatch().
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4DispatchResult
{
    public function __construct(
        /** The ebMS3 MessageId used in the outbound message */
        public string $messageId,
        /** HTTP status code from the receiver's AS4 endpoint */
        public int $httpStatus,
        /** Parsed receipt or error signal from the response body; null on HTTP 202 */
        public As4ReceiptSignal|As4ErrorSignal|null $signal,
        /** True when the receiver returned HTTP 200 or 202 */
        public bool $success,
    ) {}

    /** Returns true when the response contained a failure-severity ebMS3 error signal. */
    public function hasError(): bool
    {
        return $this->signal instanceof As4ErrorSignal && $this->signal->isFailure();
    }
}
