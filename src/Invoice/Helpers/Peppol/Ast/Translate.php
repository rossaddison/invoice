<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath translate(value, from, to) — replaces or deletes characters.
 *
 * Each character in value that appears in from is replaced by the character at
 * the same position in to; if that position is beyond the end of to, the
 * character is deleted. Commonly used in Peppol assertions to strip digits and
 * verify that only digits remain (translate(..., '0123456789', '') = '').
 */
readonly class Translate implements Expression
{
    public function __construct(
        public Expression $value,
        public Expression $from,
        public Expression $to,
    ) {}
}
