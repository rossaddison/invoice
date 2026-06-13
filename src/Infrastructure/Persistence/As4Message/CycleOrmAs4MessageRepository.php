<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use App\Invoice\As4\As4MessageRepositoryInterface;
use App\Invoice\As4\As4MessageState;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Select;
use DateTime;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * Cycle ORM implementation of As4MessageRepositoryInterface.
 *
 * All state-machine transitions are handled by As4Message methods; this class
 * is responsible only for persistence and the one atomic SQL operation
 * (claimForRetry) that must bypass the ORM to guarantee concurrency safety.
 *
 * @template TEntity of As4Message
 * @extends Select\Repository<TEntity>
 */
final class CycleOrmAs4MessageRepository extends Select\Repository implements As4MessageRepositoryInterface
{
    /** Seconds a claim lock is held before it expires and another worker may retry. */
    private const int CLAIM_TTL_SECONDS = 600;

    /**
     * @param Select<TEntity> $select
     */
    public function __construct(
        Select $select,
        private readonly EntityWriter $entityWriter,
        private readonly DatabaseInterface $database,
    ) {
        parent::__construct($select);
    }

    /**
     * @return As4Message[]
     */
    #[\Override]
    public function findPendingRetries(): array
    {
        /** @var As4Message[] */
        return $this->select()
            ->where('state', As4MessageState::sent->value)
            ->orderBy('last_attempt_at', 'ASC')
            ->fetchAll();
    }

    /**
     * @return As4Message[]
     */
    #[\Override]
    public function findAwaitingReceipts(): array
    {
        /** @var As4Message[] */
        return $this->select()
            ->where('state', As4MessageState::sent->value)
            ->where('first_sent_at', '!=', null)
            ->orderBy('first_sent_at', 'ASC')
            ->fetchAll();
    }

    #[\Override]
    public function findByMessageId(string $messageId): ?As4Message
    {
        /** @var As4Message|null */
        return $this->select()
            ->where('message_id', $messageId)
            ->fetchOne() ?: null;
    }

    /**
     * Atomically claim this message for retry.
     *
     * Issues a single UPDATE with a compound WHERE that matches only when the
     * row is still unclaimed (locked_at IS NULL) or the previous claim has
     * expired (locked_at older than CLAIM_TTL_SECONDS). Returns true when
     * exactly one row was updated — i.e., this worker won the race.
     *
     * The locked_at column is intentionally managed here via raw SQL rather
     * than through the Cycle ORM entity mapper, so the claim is atomic at the
     * database level and cannot be split across two round-trips.
     */
    #[\Override]
    public function claimForRetry(As4Message $message): bool
    {
        if (!$message->isPersisted()) {
            return false;
        }

        $now      = new DateTime();
        $expiry   = (clone $now)->modify(sprintf('-%d seconds', self::CLAIM_TTL_SECONDS));

        $affected = $this->database->execute(
            <<<SQL
            UPDATE as4_messages
               SET locked_at  = ?,
                   updated_at = ?
             WHERE id         = ?
               AND state      = ?
               AND (locked_at IS NULL OR locked_at < ?)
            SQL,
            [
                $now->format('Y-m-d H:i:s'),
                $now->format('Y-m-d H:i:s'),
                $message->reqId(),
                As4MessageState::sent->value,
                $expiry->format('Y-m-d H:i:s'),
            ],
        );

        return $affected === 1;
    }

    #[\Override]
    public function save(As4Message $message): void
    {
        $this->entityWriter->write([$message]);
    }
}
