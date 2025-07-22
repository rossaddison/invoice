<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

/**
 * All possible Unit Codes that can be used
 * To extend, see also: http://tfig.unece.org/contents/recommendation-20.htm
 */
class InvoiceTypeCode
{
    public const int INVOICE = 380;
    public const int CORRECTED_INVOICE = 384;
    public const int SELF_BILLING_INVOICE = 389;
    public const int CREDIT_NOTE = 381;
}
