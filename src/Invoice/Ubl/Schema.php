<?php

declare(strict_types=1);

namespace App\Invoice\Ubl;

class Schema
{
    // XPath / Sabre\Xml prefixes (used as element name prefixes in serialisation)
    public const string CBC = 'cbc:';
    public const string CAC = 'cac:';

    // Full namespace URIs — used for DOMXPath::registerNamespace() and Sabre\Xml namespaceMap
    public const string CBC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';
    public const string CAC_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
    public const string INVOICE_NS = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
    public const string CREDIT_NOTE_NS = 'urn:oasis:names:specification:ubl:schema:xsd:CreditNote-2';
    // Peppol BIS Ordering / Advanced Ordering (docs.peppol.eu/poacc/upgrade-3/profiles/28-ordering/,
    // .../65-advanced-ordering/) — inbound-only for this app, which always plays Seller for
    // Ordering (see App\Invoice\As4\As4OrderImportService's own docblock).
    public const string ORDER_NS = 'urn:oasis:names:specification:ubl:schema:xsd:Order-2';
    // OrderResponseAdvanced — the only Ordering document this app ever sends (outbound-only,
    // mirror image of ORDER_NS above). Same UBL root element/namespace as basic BIS 28's plain
    // OrderResponse; distinguished by ORDER_RESPONSE_ADVANCED_CUSTOMIZATION_ID below.
    public const string ORDER_RESPONSE_NS = 'urn:oasis:names:specification:ubl:schema:xsd:OrderResponse-2';
    public const string ORDER_RESPONSE_ADVANCED_CUSTOMIZATION_ID =
        'urn:fdc:peppol.eu:poacc:trns:order_response_advanced:3';
    public const string ORDER_RESPONSE_ADVANCED_PROFILE_ID =
        'urn:fdc:peppol.eu:poacc:bis:advanced_ordering:3';
}
