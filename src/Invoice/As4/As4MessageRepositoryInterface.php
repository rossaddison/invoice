<?php

declare(strict_types=1);

namespace App\Invoice\As4;

use App\Infrastructure\Persistence\As4Message\As4Message;

/**
 * Domain interface for AS4 message persistence.
 *
 * Concrete implementation lives in Infrastructure\Persistence and uses Cycle ORM.
 * As4RetryEngine depends on this interface, not on the ORM directly, so the
 * retry engine can be unit-tested without a live database.
 */
interface As4MessageRepositoryInterface
{
    /**
     * Messages in Sent state whose retry interval has elapsed and that have not
     * yet reached the maximum attempt count.
     *
     * @return As4Message[]
     */
    public function findPendingRetries(): array;

    /**
     * Messages in Sent state, regardless of timing.
     * Used by Reception Awareness to detect EBMS:0301 receipt timeouts.
     *
     * @return As4Message[]
     */
    public function findAwaitingReceipts(): array;

    public function findByMessageId(string $messageId): ?As4Message;

    /**
     * Atomically claim this message for retry by this worker.
     *
     * The implementation must use a database-level compare-and-swap (e.g.
     * UPDATE ... WHERE state = 'sent' AND locked_at IS NULL) so that only
     * one concurrent worker proceeds. Returns true when the claim succeeded,
     * false when another worker beat this one to it.
     *
     * Called from As4RetryEngine::processRetries() immediately after
     * isReadyForRetry() passes, before any HTTP work begins.
     */
    public function claimForRetry(As4Message $message): bool;

    public function save(As4Message $message): void;
}
