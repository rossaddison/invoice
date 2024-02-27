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
        isset($array['tax_rate_name']) ? $model->setTax_rate_name((string)$array['tax_rate_name']) : '';
        isset($array['tax_rate_percent'])  ? $model->setTax_rate_percent((float)$array['tax_rate_percent']) : '';
        isset($array['tax_rate_code'])  ? $model->setTax_rate_code((string)$array['tax_rate_code']) : '';
        $model->setTax_rate_default($array['tax_rate_default'] === '1' ? true : false);
        isset($array['peppol_tax_rate_code']) ? $model->setPeppol_tax_rate_code((string)$array['peppol_tax_rate_code']) : '';        
        isset($array['storecove_tax_type']) ? $model->setStorecove_tax_type((string)$array['storecove_tax_type']) : '';
        if ($model->isNewRecord()) {
            $model->setTax_rate_default(false);
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