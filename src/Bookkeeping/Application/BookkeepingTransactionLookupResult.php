<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

/**
 * Outcome of BookkeepingGatewayInterface::getTransaction(). Distinguishes
 * three states a plain nullable return can't: found (with the provider's
 * own assigned reference), genuinely not found (no $message), and a
 * provider-side failure while looking it up (has a $message) -- code
 * checking idempotency before createTransaction() needs to tell "doesn't
 * exist yet, safe to create" apart from "couldn't check, don't guess".
 *
 * Named factory methods (private constructor) rather than a public one,
 * so an invalid combination -- e.g. found=true with no providerReference
 * -- can't be constructed at all.
 */
final class BookkeepingTransactionLookupResult
{
    private function __construct(
        public readonly bool $found,
        public readonly ?string $providerReference,
        public readonly string $message,
    ) {
    }

    public static function found(string $providerReference): self
    {
        return new self(true, $providerReference, '');
    }

    public static function notFound(): self
    {
        return new self(false, null, '');
    }

    public static function failed(string $message): self
    {
        return new self(false, null, $message);
    }
}
