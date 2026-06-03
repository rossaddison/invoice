<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 "for $var in sequence return expr" — produces a flat sequence by
 * evaluating the return expression once for each item in the source sequence.
 *
 * Used in Peppol assertions for string-level numeric calculations, e.g.:
 *   for $cp in string-to-codepoints(str) return (if ($cp > 64) then ... else ...)
 *
 * The return expression is evaluated with $var bound to each item; results are
 * collected as a PHP array suitable for further processing (e.g. string-join).
 */
readonly class ForExpression implements Expression
{
    public function __construct(
        public string     $variable,
        public Expression $in,
        public Expression $return,
    ) {}
}
