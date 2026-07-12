<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol;

/**
 * One tax category available under a {@see PeppolProfile}: a UNCL5305 code,
 * its rate (null for categories with no single fixed rate, e.g. Exempt), and
 * a human-readable label.
 */
final readonly class TaxCategorySpec
{
    public function __construct(
        public string $code,
        public ?float $rate,
        public string $label,
    ) {
    }
}
