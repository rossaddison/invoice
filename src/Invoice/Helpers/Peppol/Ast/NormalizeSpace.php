<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath normalize-space(value?) — strips leading/trailing whitespace and
 * collapses internal whitespace runs to a single space.
 *
 * Called with no argument it normalises the string-value of the context node.
 * The parser supplies Path('.') as the default argument in that case.
 *
 * Very common in Peppol assertions: normalize-space(cbc:ID) != ''
 */
readonly class NormalizeSpace implements Expression
{
    public function __construct(public Expression $value) {}
}
