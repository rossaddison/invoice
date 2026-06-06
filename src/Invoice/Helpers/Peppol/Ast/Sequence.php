<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 sequence constructor: (A, B, C, ...)
 *
 * In boolean context the effective boolean value is true when the merged
 * node-set of all items is non-empty (i.e. at least one item matches something).
 *
 * Used in Peppol assertions such as:
 *   (cac:TaxRepresentativeParty, $BT-31orBT-32Path)
 * to express "any of these paths is present".
 */
readonly class Sequence implements Expression
{
    /** @param non-empty-list<Expression> $items */
    public function __construct(public array $items) {}
}
