<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Reliability configuration section of a PMode (reception awareness, retry, duplicate detection).
 */
class PModeReliability
{
    private bool $receptionAwarenessEnabled = true;
    private bool $retryEnabled = true;
    private int $retryMaxRetries = 3;
    private int $retryIntervalSeconds = 300;
    private bool $duplicateDetectionEnabled = true;

    public function isReceptionAwarenessEnabled(): bool { return $this->receptionAwarenessEnabled; }
    public function isRetryEnabled(): bool { return $this->retryEnabled; }
    public function getMaxRetries(): int { return $this->retryMaxRetries; }
    public function getRetryIntervalSeconds(): int { return $this->retryIntervalSeconds; }
    public function isDuplicateDetectionEnabled(): bool { return $this->duplicateDetectionEnabled; }

    public function setMaxRetries(int $retries): self
    {
        $this->retryMaxRetries = $retries;
        return $this;
    }

    public function setRetryIntervalSeconds(int $seconds): self
    {
        $this->retryIntervalSeconds = $seconds;
        return $this;
    }
}
