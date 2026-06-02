<?php

declare(strict_types=1);

/**
 * What?  The small subset of UN/CEFACT code list 2005 (Date/Time/Period function codes)
 *        that Peppol permits for the invoice period description code:
 *          '3'   = Document/message date/time (proforma)
 *          '35'  = Actual delivery date/time (invoice)
 *          '432' = Paid as agreed (credit note period)
 * Why?   The cbc:DescriptionCode element inside cac:InvoicePeriod must come from this
 *        approved subset; any other value triggers validation rule BR-CL-23.
 * When?  Loaded once per process by CodeList::load(CodeLists::UNCL2005) the first time
 *        an invoice period code is checked; the result is held in CodeList's static cache.
 * Where? Consumed inside PeppolValidator::validateInvoicePeriodDescriptionCode() via
 *        CodeList::contains() against //cac:InvoicePeriod/cbc:DescriptionCode.
 * How?   Returns a small flat PHP array of strings. PHP opcache compiles this file to
 *        bytecode so no parsing overhead is incurred on subsequent requests.
 *
 * Quarterly update: compare against the UN/CEFACT 2005 subset defined in the Peppol
 * BIS Billing 3.0 specification at https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL2005/
 */
return ['3', '35', '432'];
