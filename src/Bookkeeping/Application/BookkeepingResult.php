<?php

declare(strict_types=1);

namespace App\Bookkeeping\Application;

/**
 * Outcome of a createTransaction()/updateTransaction()/deleteTransaction()
 * call against a BookkeepingGatewayInterface implementation. Never thrown
 * for an expected provider-side failure -- mirrors
 * App\Invoice\PaymentInformation\PaymentRefundResult's own convention, so
 * a caller can flash $message rather than wrap every gateway call in
 * try/catch. $providerReference is the provider's own assigned id for the
 * transaction (e.g. QuickBooks' JournalEntry id) -- empty when $success is false or
 * the operation (delete) has none to give.
 */
final class BookkeepingResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $providerReference = '',
        public readonly string $message = '',
    ) {
    }
}
