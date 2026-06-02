<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * A raw XPath location path, delegated to DOMXPath unchanged.
 *
 * Replaces the Path node.  The name PathExpression makes the role clearer:
 * this node carries a path string that is XPath 1.0 compatible and can be
 * passed directly to DOMXPath::evaluate() / ::query().
 *
 * Paths containing XPath 2.0 constructs (xs:decimal(), exists(), upper-case()
 * etc.) must be represented using the appropriate typed AST nodes rather than
 * being embedded as raw strings here.
 *
 * Examples:
 *   //cac:InvoiceLine/cbc:LineExtensionAmount
 *   cac:TaxTotal[cac:TaxSubtotal]/cbc:TaxAmount
 *   @currencyID
 *   .
 */

/**
 * @psalm-suppress UnusedClass
 */
readonly class PathExpression implements Expression
{
    public function __construct(public string $xpath) {}
}
