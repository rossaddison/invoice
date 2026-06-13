<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Exponential back-off retry policy with optional random jitter.
 *
 * delay = min(baseInterval × multiplier^(attempt-1), maxDelay) + rand(0, jitterCeiling)
 *
 * For the first attempt (attemptCount = 0) the exponent is 0 so the delay
 * equals baseInterval — identical to the fixed-interval policy.  Each
 * subsequent failure doubles (with the default multiplier of 2.0) up to
 * the configured cap.  Jitter spreads retries from many senders to avoid
 * the thundering-herd effect on a recovering access point.
 *
 * Set jitterCeiling to 0 for deterministic behaviour in tests.
 */
final class As4ExponentialBackoffRetryPolicy implements As4RetryPolicyInterface
{
    public function __construct(
        private readonly int $maxDelaySeconds = 3600,
        private readonly float $multiplier = 2.0,
        private readonly int $jitterCeiling = 30,
    ) {}

    #[\Override]
    public function delaySeconds(int $attemptCount, int $baseIntervalSeconds): int
    {
        $exponential = (int) ($baseIntervalSeconds * ($this->multiplier ** max(0, $attemptCount - 1)));
        $capped      = min($exponential, $this->maxDelaySeconds);
        $jitter      = $this->jitterCeiling > 0 ? random_int(0, $this->jitterCeiling) : 0;
        return $capped + $jitter;
    }
}
