<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

/**
 * The small, closed set of account categories this app's bookkeeping
 * events map onto -- deliberately NOT a chart of accounts. Xero, Sage,
 * Akaunting and QuickBooks each already own their user's real chart of
 * accounts; mirroring that here would just be a second copy to keep in
 * sync for no benefit. Each provider gateway
 * (App\Bookkeeping\Infrastructure\{Provider}) owns its own small mapping
 * from a role here to that provider's actual configured account code
 * (Settings-driven, same shape as this app's existing
 * gateway_{driver}_* credential settings).
 *
 * Grows slowly and deliberately as new accounting event types are added
 * -- see BookkeepingTransactionType for the events these roles currently
 * need to serve.
 */
enum AccountRole: string
{
    case AccountsReceivable = 'accounts_receivable';
    case Sales = 'sales';
    case Vat = 'vat';
    case Bank = 'bank';
    case PaymentFees = 'payment_fees';
}
