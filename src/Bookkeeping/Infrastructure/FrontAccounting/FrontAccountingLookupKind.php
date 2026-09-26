<?php

declare(strict_types=1);

namespace App\Bookkeeping\Infrastructure\FrontAccounting;

/**
 * Which FrontAccounting reference list a cached FrontAccountingLookup row
 * belongs to. Lives in Infrastructure, not Domain -- unlike AccountRole,
 * this isn't a core ledger concept, it's purely so the Online Bookkeeping
 * settings tab can render FrontAccountingGateway's id-typed settings
 * (bank_account, stock_id, vat_stock_id, tax_group, payment_terms) as
 * dropdowns instead of raw text inputs.
 */
enum FrontAccountingLookupKind: string
{
    case TaxGroup = 'tax_group';
    case StockItem = 'stock_item';
    case BankAccount = 'bank_account';
    case PaymentTerms = 'payment_terms';
}
