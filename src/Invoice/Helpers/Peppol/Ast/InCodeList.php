<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

use App\Invoice\Helpers\Peppol\CodeLists;

/**
 * Membership test against a Peppol code list.
 *
 * Corresponds to the Schematron pattern:
 *   some $x in tokenize('0088 0192 ...', '\s') satisfies normalize-space(.) = $x
 *
 * Instead of encoding the list inline in the expression tree, this node
 * references the CodeLists enum so the actual values are always read from
 * resources/peppol/*.php (and benefit from quarterly updates there).
 *
 * PHP evaluation: CodeList::contains($list, resolved string value of $value).
 */
readonly class InCodeList implements Expression
{
    public function __construct(
        public Expression $value,
        public CodeLists  $list,
    ) {}
}
