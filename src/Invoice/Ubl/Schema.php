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
}
