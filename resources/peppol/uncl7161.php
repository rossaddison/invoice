<?php

declare(strict_types=1);

/**
 * What?  UN/CEFACT code list 7161 (Special service description code) values that Peppol
 *        permits as charge reason codes — a large set of two-to-three-letter strings, e.g.:
 *          'AA'  = Advertising
 *          'ADR' = Air delivery
 *          'FC'  = Freight charge
 *          'SM'  = Special minimum order quantity surcharge
 * Why?   When cbc:ChargeIndicator is 'true' (meaning it is a charge, not an allowance),
 *        the AllowanceChargeReasonCode must come from this list; any other value triggers
 *        validation rule BR-CL-21.
 * When?  Loaded once per process by CodeList::load(CodeLists::UNCL7161) the first time a
 *        charge reason code is checked; the result is held in CodeList's static cache.
 * Where? Consumed inside PeppolValidator::validateAllowanceChargeReasonCodes() via
 *        CodeList::contains() for AllowanceCharge nodes where ChargeIndicator = 'true'.
 * How?   Returns a flat PHP array of strings sorted alphabetically. PHP opcache compiles
 *        this file to bytecode so no parsing overhead is incurred on subsequent requests.
 *
 * Quarterly update: compare against the UN/CEFACT 7161 subset defined in the Peppol
 * BIS Billing 3.0 specification at https://docs.peppol.eu/poacc/billing/3.0/codelist/UNCL7161/
 */
return [
    'AA',  'AAA', 'AAC', 'AAD', 'AAE', 'AAF', 'AAH', 'AAI', 'AAS', 'AAT',
    'AAV', 'AAY', 'AAZ', 'ABA', 'ABB', 'ABC', 'ABD', 'ABF', 'ABK', 'ABL',
    'ABN', 'ABR', 'ABS', 'ABT', 'ABU', 'ACF', 'ACG', 'ACH', 'ACI', 'ACJ',
    'ACK', 'ACL', 'ACM', 'ACS', 'ADC', 'ADE', 'ADJ', 'ADK', 'ADL', 'ADM',
    'ADN', 'ADO', 'ADP', 'ADQ', 'ADR', 'ADT', 'ADW', 'ADY', 'ADZ', 'AEA',
    'AEB', 'AEC', 'AED', 'AEF', 'AEH', 'AEI', 'AEJ', 'AEK', 'AEL', 'AEM',
    'AEN', 'AEO', 'AEP', 'AES', 'AET', 'AEU', 'AEV', 'AEW', 'AEX', 'AEY',
    'AEZ', 'AJ',  'AU',  'CA',  'CAB', 'CAD', 'CAE', 'CAF', 'CAI', 'CAJ',
    'CAK', 'CAL', 'CAM', 'CAN', 'CAO', 'CAP', 'CAQ', 'CAR', 'CAS', 'CAT',
    'CAU', 'CAV', 'CAW', 'CAX', 'CAY', 'CAZ', 'CD',  'CG',  'CS',  'CT',
    'DAB', 'DAC', 'DAD', 'DAF', 'DAG', 'DAH', 'DAI', 'DAJ', 'DAK', 'DAL',
    'DAM', 'DAN', 'DAO', 'DAP', 'DAQ', 'DL',  'EG',  'EP',  'ER',  'FAA',
    'FAB', 'FAC', 'FC',  'FH',  'FI',  'GAA', 'HAA', 'HD',  'HH',  'IAA',
    'IAB', 'ID',  'IF',  'IR',  'IS',  'KO',  'L1',  'LA',  'LAA', 'LAB',
    'LF',  'MAE', 'MI',  'ML',  'NAA', 'OA',  'PA',  'PAA', 'PC',  'PL',
    'PRV', 'RAB', 'RAC', 'RAD', 'RAF', 'RE',  'RF',  'RH',  'RV',  'SA',
    'SAA', 'SAD', 'SAE', 'SAI', 'SG',  'SH',  'SM',  'SU',  'TAB', 'TAC',
    'TT',  'TV',  'V1',  'V2',  'WH',  'XAA', 'YY',  'ZZZ',
];
