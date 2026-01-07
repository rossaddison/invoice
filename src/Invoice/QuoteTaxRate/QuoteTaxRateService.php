<?php

declare(strict_types=1);

namespace App\Invoice\QuoteTaxRate;

use App\Invoice\Entity\QuoteTaxRate;
use App\Invoice\Quote\QuoteRepository;
use App\Invoice\TaxRate\TaxRateRepository;

final readonly class QuoteTaxRateService
{
    public function __construct(
        private QuoteTaxRateRepository $repository,
        private QuoteRepository $quoteRepository,
        private TaxRateRepository $taxRateRepository,
    ) {
    }

    private function persist(
        QuoteTaxRate $model,
        array $array
    ): void {
        $quote = $this->quoteRepository->repoQuoteUnLoadedquery(
            (string) $array['quote_id']
        );
        if ($quote) {
            $model->setQuote($quote);
            $model->setQuote_id((int) $quote->getId());
        }
        $tax_rate = $this->taxRateRepository->repoTaxRatequery(
            (string) $array['tax_rate_id']
        );
        if ($tax_rate) {
            $model->setTaxRate($tax_rate);
            $model->setTax_rate_id(
                (int) $tax_rate->getTaxRateId()
            );
        }
    }

    /**
     * @param QuoteTaxRate $model
     * @param array $array
     */
    public function saveQuoteTaxRate(
        QuoteTaxRate $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $model->setInclude_item_tax(
            (int) $array['include_item_tax'] ?: 0
        );
        isset($array['tax_rate_amount'])
            ? $model->setQuote_tax_rate_amount(
                (float) $array['quote_tax_rate_amount']
            )
            : '';
        $this->repository->save($model);
    }

    /**
     * @param array|QuoteTaxRate|null $model
     */
    public function deleteQuoteTaxRate(
        array|QuoteTaxRate|null $model
    ): void {
        $this->repository->delete($model);
    }
}
