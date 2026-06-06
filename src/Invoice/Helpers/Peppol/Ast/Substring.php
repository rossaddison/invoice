<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath substring(value, start [, length]) — returns a portion of a string.
 *
 * start is 1-based (XPath convention); length is optional.
 * PHP evaluation converts to 0-based mb_substr.
 */
readonly class Substring implements Expression
{
    public function __construct(
        public Expression  $value,
        public Expression  $start,
        public ?Expression $length,
    ) {}
}
