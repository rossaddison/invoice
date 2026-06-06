<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 "expr castable as TypeName" — returns true when expr can be cast
 * to the named atomic type without error.
 *
 * Used in Peppol assertions to validate field formats, e.g.
 *   string(cbc:ID) castable as xs:date
 *
 * Supported type names: xs:date, xs:integer, xs:long, xs:int,
 *   xs:decimal, xs:float, xs:double, xs:boolean, xs:string.
 */
readonly class CastableAs implements Expression
{
    public function __construct(
        public Expression $value,
        public string     $typeName,
    ) {}
}
