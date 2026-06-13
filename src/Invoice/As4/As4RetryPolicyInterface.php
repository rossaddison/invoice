<?php

declare(strict_types=1);

namespace App\Invoice\As4;

interface As4RetryPolicyInterface
{
    /**
     * Compute the delay in seconds before the next retry attempt.
     *
     * @param int $attemptCount       Attempts already made (0 = none yet).
     * @param int $baseIntervalSeconds The message's configured base retry interval.
     */
    public function delaySeconds(int $attemptCount, int $baseIntervalSeconds): int;
}
