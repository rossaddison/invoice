<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Rule;

/**
 * Document-level state extracted from the XML before rule evaluation begins.
 *
 * Passed to every ValidationRule::validate() call so individual rules can
 * make country-specific or type-specific decisions without re-querying the DOM.
 *
 * What?  A read-only snapshot of the fields PeppolValidator extracts during loadXML().
 * Why?   Rules need context (supplier country, document type, etc.) that is expensive
 *        to re-derive per rule. Centralising it here avoids repeated XPath queries and
 *        makes rule implementations simpler and testable in isolation.
 * When?  Created once per validate() call in PeppolValidator::validateWithRegistry()
 *        and passed unchanged to every rule in the RuleRegistry.
 * Where? Consumed by individual ValidationRule implementations (e.g. PEPPOL_EN16931_R002
 *        needs supplierCountry and customerCountry for the DE-to-DE note relaxation).
 * How?   Plain readonly constructor promotion — no behaviour, no mutation.
 */
final class ValidationContext
{
    public function __construct(
        /** 'Invoice' or 'CreditNote', null if the root element was unrecognised. */
        public readonly string|null $documentType,

        /** Three-letter ISO 4217 currency code from cbc:DocumentCurrencyCode. */
        public readonly string|null $documentCurrencyCode,

        /** Two-letter ISO 3166 country code derived from the seller's VAT ID or address. */
        public readonly string|null $supplierCountry,

        /** Two-letter ISO 3166 country code derived from the buyer's VAT ID or address. */
        public readonly string|null $customerCountry,

        /** Profile number extracted from cbc:ProfileID (e.g. '01'), or translated 'unknown'. */
        public readonly string|null $profile,
    ) {}
}
