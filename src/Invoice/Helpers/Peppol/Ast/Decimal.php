<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Ast;

/**
 * XPath 2.0 xs:decimal(value) constructor — casts a node value to decimal.
 *
 * The most-used XPath 2.0 construct in the Schematron (~230 occurrences).
 * Every monetary arithmetic rule passes amounts through this cast before
 * addition, subtraction, or round().
 *
 * PHP evaluation: cast the node's string value to float, preserving two
 * decimal places via bcmath or number_format where precision matters.
 */
readonly class Decimal implements Expression
{
    public function __construct(public Expression $value) {}
}
