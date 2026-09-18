<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

use App\Bookkeeping\Domain\BookkeepingTransaction;

/**
 * Common surface a bookkeeping provider integration exposes (currently
 * App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway), regardless
 * of how its own REST API is shaped. Mirrors
 * App\Invoice\PaymentInformation\PaymentGatewayInterface's role for
 * payment gateways -- including its "never throw on provider-side
 * failure, report via a Result object" convention -- so a caller here has
 * the same ability to flash a message or retry, rather than wrapping
 * every gateway call in try/catch.
 *
 * A port -- "a capability the application needs from the outside" -- so
 * it lives in Application, not Domain; Domain has no idea external export
 * exists at all.
 */
interface BookkeepingGatewayInterface
{
    /**
     * Matches the suffix used in a `bookkeeping_{driver}_enabled`-style
     * setting key, e.g. 'quickbooks'.
     */
    public function getDriverKey(): string;

    /**
     * True when the credentials/tokens this gateway needs to operate are
     * present.
     */
    public function isConfigured(): bool;

    public function createTransaction(BookkeepingTransaction $transaction): BookkeepingResult;

    public function updateTransaction(BookkeepingTransaction $transaction): BookkeepingResult;

    /**
     * Looks up a previously created transaction by this app's own
     * $reference (its idempotency key -- see BookkeepingTransaction's own
     * docblock), not the provider's assigned id, so a caller can check
     * "did I already post this" without having stored the provider's id
     * yet -- the exact situation a lost/timed-out create response leaves
     * behind.
     */
    public function getTransaction(string $reference): BookkeepingTransactionLookupResult;

    public function deleteTransaction(string $reference): BookkeepingResult;
}
