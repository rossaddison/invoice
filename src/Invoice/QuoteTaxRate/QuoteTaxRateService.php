<?php

declare(strict_types=1);

namespace App\Invoice\QuoteTaxRate;

use App\Invoice\Entity\QuoteTaxRate;

final readonly class QuoteTaxRateService
{
    public function __construct(private QuoteTaxRateRepository $repository)
    {
    }

    public function saveQuoteTaxRate(QuoteTaxRate $model, array $array): void
    {
        isset($array['quote_id']) ? $model->setQuote_id((int) $array['quote_id']) : '';
        isset($array['tax_rate_id']) ? $model->setTax_rate_id((int) $array['tax_rate_id']) : '';
        $model->setInclude_item_tax((int) $array['include_item_tax'] ?: 0);
        isset($array['tax_rate_amount']) ? $model->setQuote_tax_rate_amount((float) $array['quote_tax_rate_amount']) : '';

        $this->repository->save($model);
    }

    public function deleteQuoteTaxRate(array|QuoteTaxRate|null $model): void
    {
        $this->repository->delete($model);
    }
}
