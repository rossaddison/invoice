<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\As4Message;

use Cycle\Annotated\Annotation as Cycle;
use DateTimeImmutable;

#[Cycle\Embeddable]
class As4RetryState
{
    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $attemptCount = 0;

    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $maxAttempts = 3;

    #[Cycle\Column(type: 'integer', nullable: false)]
    private int $retryIntervalSeconds = 300;

    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $lastAttemptAt = null;

    /**
     * Timestamp of the very first transmission attempt (receipt deadline anchor).
     */
    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $firstSentAt = null;

    /**
     * Claim lock timestamp. Null = unclaimed. Set atomically by
     * CycleOrmAs4MessageRepository::claimForRetry() to prevent two concurrent
     * workers from retrying the same message.
     */
    #[Cycle\Column(type: 'datetime', nullable: true)]
    private ?DateTimeImmutable $lockedAt = null;

    public function getAttemptCount(): int { return $this->attemptCount; }
    public function getMaxAttempts(): int { return $this->maxAttempts; }
    public function getRetryIntervalSeconds(): int { return $this->retryIntervalSeconds; }
    public function getLastAttemptAt(): ?DateTimeImmutable { return $this->lastAttemptAt; }
    public function getFirstSentAt(): ?DateTimeImmutable { return $this->firstSentAt; }
    public function getLockedAt(): ?DateTimeImmutable { return $this->lockedAt; }

    public function recordAttempt(): void
    {
        $this->attemptCount++;
        $this->lastAttemptAt = new DateTimeImmutable();
    }

    public function recordSent(): void
    {
        if ($this->firstSentAt === null) {
            $this->firstSentAt = new DateTimeImmutable();
        }
        $this->lastAttemptAt = new DateTimeImmutable();
        $this->attemptCount++;
    }

    public function isEligible(int $intervalSeconds = 0): bool
    {
        if ($this->attemptCount >= $this->maxAttempts) {
            return false;
        }
        if ($this->lastAttemptAt === null) {
            return true;
        }
        $delay     = $intervalSeconds > 0 ? $intervalSeconds : $this->retryIntervalSeconds;
        $nextRetry = $this->lastAttemptAt->modify("+{$delay} seconds");
        return new DateTimeImmutable() >= $nextRetry;
    }

    public function secondsUntilNextRetry(): ?int
    {
        if ($this->lastAttemptAt === null) {
            return null;
        }
        $nextRetry = $this->lastAttemptAt->modify("+{$this->retryIntervalSeconds} seconds");
        $diff = $nextRetry->diff(new DateTimeImmutable());
        return max(0, (int) $diff->format('%s'));
    }
}
