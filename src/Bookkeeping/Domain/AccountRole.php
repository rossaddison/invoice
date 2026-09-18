<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

/**
 * The small, closed set of account categories this app's bookkeeping
 * events map onto -- deliberately NOT a chart of accounts. QuickBooks
 * already owns the user's real chart of accounts; mirroring that here
 * would just be a second copy to keep in sync for no benefit.
 * App\Bookkeeping\Infrastructure\QuickBooks\QuickBooksGateway owns its
 * own small mapping from a role here to QuickBooks' actual configured
 * account code (Settings-driven, same shape as this app's existing
 * gateway_{driver}_* credential settings).
 *
 * VatOrTax rather than just Vat: this app is seeing real interest from
 * the US, where the equivalent concept is sales tax, not VAT -- a
 * UK-only term baked into the domain model is exactly the kind of thing
 * a genuinely universal bookkeeping layer (the whole point of this
 * module) can't afford. Jurisdiction-specific detail belongs in
 * $taxCode on BookkeepingLine (a plain string) or in a provider's own
 * account-code mapping, never hidden inside this enum's own case names.
 *
 * Grows slowly and deliberately as new accounting event types are added
 * -- see BookkeepingTransactionType for the events these roles currently
 * need to serve.
 */
enum AccountRole: string
{
    case AccountsReceivable = 'accounts_receivable';
    case Sales = 'sales';
    case VatOrTax = 'vat_or_tax';
    case Bank = 'bank';
    case PaymentFees = 'payment_fees';
}
