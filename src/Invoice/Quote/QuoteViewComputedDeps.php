<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Infrastructure\Persistence\Quote\Quote;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeForm;

/**
 * Values `Trait\View::view()` computes once per request before building
 * its view-data array — bundled here, the same way `QuoteViewCoreDeps`/
 * `QuoteViewItemDeps`/`QuoteViewRenderDeps`/`QuoteViewUIDeps` already
 * bundle that method's injected repositories, so extracting the array
 * itself into its own private method (SonarQube php:S138 — `view()` was
 * over the 150-line ceiling) doesn't just trade it for a php:S107
 * too-many-parameters violation instead. $quote itself lives here too
 * (rather than as its own buildViewParameters() parameter) for the same
 * reason -- confirmed live: without it, that method's own 8 parameters
 * tripped php:S107's 7-parameter ceiling.
 */
final class QuoteViewComputedDeps
{
    public function __construct(
        public readonly Quote $quote,
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
