<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 quantified expression: some $variable in $in satisfies $satisfies.
 *
 * True when at least one item in the sequence bound to $variable satisfies
 * the predicate expression.  Used 9 times in the Schematron, primarily for
 * code-list membership tests:
 *   some $x in tokenize('380 381 384', '\s') satisfies normalize-space(cbc:InvoiceTypeCode) = $x
 *
 * For code-list membership, prefer InCodeList (which avoids inline lists).
 * Some is needed for structural checks that can't be expressed as InCodeList.
 *
 * $variable is the variable name without the leading $, e.g. 'x'.
 */
readonly class Some implements Expression
{
    public function __construct(
        public string     $variable,
        public Expression $in,
        public Expression $satisfies,
    ) {}
}
