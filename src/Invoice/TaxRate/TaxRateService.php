<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;

final readonly class TaxRateService
{
    public function __construct(private TaxRateRepository $repository)
    {
    }

    public function saveTaxRate(TaxRate $model, array $array): void
    {
        isset($array['tax_rate_name']) ? $model->setTaxRateName((string) $array['tax_rate_name']) : '';
        isset($array['tax_rate_percent']) ? $model->setTaxRatePercent((float) $array['tax_rate_percent']) : '';
        isset($array['tax_rate_code']) ? $model->setTaxRateCode((string) $array['tax_rate_code']) : '';
        $model->setTaxRateDefault('1' === $array['tax_rate_default'] ? true : false);
        isset($array['peppol_tax_rate_code']) ? $model->setPeppolTaxRateCode((string) $array['peppol_tax_rate_code']) : '';
        isset($array['storecove_tax_type']) ? $model->setStorecoveTaxType((string) $array['storecove_tax_type']) : '';
        if ($model->isNewRecord()) {
            $model->setTaxRateDefault(false);
        }
        $this->repository->save($model);
    }

    public function deleteTaxRate(TaxRate $model): void
    {
        $this->repository->delete($model);
    }
}
