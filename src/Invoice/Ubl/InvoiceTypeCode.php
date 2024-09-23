<?php
declare(strict_types=1); 

namespace App\Invoice\Ubl;

/**
 * All possible Unit Codes that can be used
 * To extend, see also: http://tfig.unece.org/contents/recommendation-20.htm
 */
class InvoiceTypeCode
{
    const int INVOICE = 380;
    const int CORRECTED_INVOICE = 384;
    const int SELF_BILLING_INVOICE = 389;
    const int CREDIT_NOTE = 381;
}
