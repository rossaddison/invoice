<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeForm;

/**
 * Values `Trait\View::view()` computes once per request before building
 * its view-data array — bundled here, the same way `QuoteViewCoreDeps`/
 * `QuoteViewItemDeps`/`QuoteViewRenderDeps`/`QuoteViewUIDeps` already
 * bundle that method's injected repositories, so extracting the array
 * itself into its own private method (SonarQube php:S138 — `view()` was
 * over the 150-line ceiling) doesn't just trade it for a php:S107
 * too-many-parameters violation instead.
 */
final class QuoteViewComputedDeps
{
    public function __construct(
        public readonly bool $quoteEdit,
        public readonly ?float $quoteAmountTotal,
        public readonly string $salesOrderNumber,
        public readonly string $vat,
        public readonly mixed $quoteTaxRates,
        public readonly mixed $quoteAmount,
        /** @var array<array-key, mixed> */
        public readonly array $quoteCustomValues,
        /** @var array<array-key, mixed> */
        public readonly array $customValues,
        public readonly QuoteAllowanceChargeForm $quoteAllowanceChargeForm,
    ) {
    }
}
