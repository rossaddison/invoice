<?php

declare(strict_types=1);

/**
 * What?  UN/CEFACT code list 5189 (Allowance or charge identification code) values
 *        that Peppol permits as allowance reason codes, e.g.:
 *          '41' = Bonus
 *          '42' = Trade discount
 *          '60' = Manufacturer's consumer discount
 *          '95' = Special rebate
 * Why?   When cbc:ChargeIndicator is 'false' (meaning it is an allowance, not a charge),
 *        the AllowanceChargeReasonCode must come from this list; any other value triggers
 *        validation rule BR-CL-20.
 * When?  Loaded once per process by CodeList::load(CodeLists::UNCL5189) the first time
 *        an allowance reason code is checked; the result is held in CodeList's static cache.
 * Where? Consumed inside PeppolValidator::validateAllowanceChargeReasonCodes() via
 *        CodeList::contains() for AllowanceCharge nodes where ChargeIndicator = 'false'.
 * How?   Returns a flat PHP array of strings. PHP opcache compiles this file to bytecode
 *        so no parsing overhead is incurred on subsequent requests.
 *
 * Quarterly update: compare against the UN/CEFACT 5189 subset defined in the Peppol
 * BIS Billing 3.0 specification at https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL5189/
 */
return [
    '41', '42', '60', '62', '63', '64', '65', '66', '67', '68',
    '70', '71', '88', '95', '100', '102', '103', '104', '105',
];
