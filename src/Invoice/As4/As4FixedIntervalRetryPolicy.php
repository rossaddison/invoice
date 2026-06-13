<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Retry policy that waits a constant interval between every attempt.
 * This is the default, backward-compatible behaviour.
 */
final class As4FixedIntervalRetryPolicy implements As4RetryPolicyInterface
{
    #[\Override]
    public function delaySeconds(int $attemptCount, int $baseIntervalSeconds): int
    {
        return $baseIntervalSeconds;
    }
}
