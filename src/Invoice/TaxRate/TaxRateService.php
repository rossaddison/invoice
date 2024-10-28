<?php

declare(strict_types=1);

namespace App\Invoice\TaxRate;

use App\Invoice\Entity\TaxRate;

final class TaxRateService
{
    private TaxRateRepository $repository;

    public function __construct(TaxRateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param TaxRate $model
     * @param array $array
     * @return void
     */
    public function saveTaxRate(TaxRate $model, array $array): void
    {
        isset($array['tax_rate_name']) ? $model->setTaxRateName((string)$array['tax_rate_name']) : '';
        isset($array['tax_rate_percent'])  ? $model->setTaxRatePercent((float)$array['tax_rate_percent']) : '';
        isset($array['tax_rate_code'])  ? $model->setTaxRateCode((string)$array['tax_rate_code']) : '';
        $model->setTaxRateDefault($array['tax_rate_default'] === '1' ? true : false);
        isset($array['peppol_tax_rate_code']) ? $model->setPeppolTaxRateCode((string)$array['peppol_tax_rate_code']) : '';        
        isset($array['storecove_tax_type']) ? $model->setStorecoveTaxType((string)$array['storecove_tax_type']) : '';
        if ($model->isNewRecord()) {
            $model->setTaxRateDefault(false);
        }
        $this->repository->save($model);
    }
    
    /**
     * @param TaxRate $model
     * @return void
     */
    public function deleteTaxRate(TaxRate $model): void
    {
        $this->repository->delete($model);
    }
}