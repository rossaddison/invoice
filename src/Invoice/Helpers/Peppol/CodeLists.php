<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

/**
 * Identifiers for every Peppol / EN16931 code list stored in resources/peppol/.
 *
 * The string value of each case is the JSON filename (without extension) under
 * resources/peppol/, so adding a new code list means dropping in a JSON file
 * and adding a case here.
 *
 * Quarterly updates: edit the matching JSON file — no PHP changes required.
 */
enum CodeLists: string
{
    /** ISO 3166-1 alpha-2 country codes (BR-CL-14). */
    case ISO3166  = 'iso3166';

    /** ISO 4217 currency codes (BR-CL-04, BR-CL-05). */
    case ISO4217  = 'iso4217';

    /** Peppol Electronic Address Identifier scheme IDs (PEPPOL-CL-0008). */
    case EAID     = 'eaid';

    /** Permitted MIME types for embedded binary attachments (BR-CL-24). */
    case MIME     = 'mime';

    /** UN/CEFACT 2005 — invoice period description codes (BR-CL-23). */
    case UNCL2005 = 'uncl2005';

    /** UN/CEFACT 5189 — allowance reason codes (BR-CL-20). */
    case UNCL5189 = 'uncl5189';

    /** UN/CEFACT 7161 — charge reason codes (BR-CL-21). */
    case UNCL7161 = 'uncl7161';
}
