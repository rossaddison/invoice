<?php

declare(strict_types=1);

namespace App\Invoice\As4;

/**
 * Immutable result of an As4NrrValidator::validate() call.
 *
 * @psalm-suppress UnusedClass
 */
final readonly class As4NrrResult
{
    private function __construct(
        public bool $valid,
        public string $reason,
    ) {}

    public static function success(): self
    {
        return new self(valid: true, reason: '');
    }

    public static function failure(string $reason): self
    {
        return new self(valid: false, reason: $reason);
    }
}
