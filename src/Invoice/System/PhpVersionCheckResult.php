<?php

declare(strict_types=1);

namespace App\Invoice\System;

/**
 * Result of comparing the running PHP version against the latest release
 * php.net reports for the same major.minor branch.
 */
final readonly class PhpVersionCheckResult
{
    public function __construct(
        public string $currentVersion,
        public ?string $latestVersion,
        public bool $isOutdated,
        public bool $isSecurityRelease,
        public ?string $checkedAt,
        public ?string $error,
    ) {
    }
}
