<?php

declare(strict_types=1);

namespace App\Bookkeeping\Domain;

/**
 * Which accounting event a BookkeepingTransaction represents. One Inv can
 * generate several of these over its life -- issuing it, a payment
 * against it, the payment provider's merchant fee, a refund -- each its
 * own BookkeepingTransaction with its own balanced lines, never a single
 * mutable "export this invoice" record. Mirrors
 * App\Invoice\Enum\StockMovementType's role for stock movements.
 */
enum BookkeepingTransactionType: string
{
    // Dr AccountsReceivable / Cr Sales / Cr VatOrTax
    case InvoiceIssued = 'invoice_issued';

    // Dr Bank / Cr AccountsReceivable
    case PaymentReceived = 'payment_received';

    // Dr PaymentFees / Cr Bank
    case MerchantFee = 'merchant_fee';

    // Reversal of InvoiceIssued and/or PaymentReceived's lines
    case Refund = 'refund';
}
