<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A raw XPath location path, passed through to DOMXPath unchanged.
 *
 * This is the leaf node for any path expression that is already XPath 1.0
 * compatible and can be handed directly to DOMXPath::query() or ::evaluate().
 * Examples:
 *   //cac:InvoiceLine/cbc:LineExtensionAmount
 *   cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount
 *   @currencyID
 *
 * Paths containing XPath 2.0 constructs (xs:decimal(), exists(), upper-case()
 * etc.) must be represented using the appropriate typed AST nodes instead of
 * being embedded as raw strings here.
 */
readonly class Path implements Expression
{
    public function __construct(public string $xpath) {}
}
