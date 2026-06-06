<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Peppol\Calculator;

/**
 * Validates invoice-line-level business rules.
 *
 * Currently a placeholder — line-level BR-CO rules (e.g. BR-CO-04, BR-CO-05)
 * will be added here as they are introduced.
 */
class InvoiceLineCalculator extends AbstractCalculator
{
    #[\Override]
    public function validate(): void
    {
        // Line-level rules to be implemented here.
    }
}
