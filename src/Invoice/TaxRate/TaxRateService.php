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
     * 
     * @param TaxRate $model
     * @param TaxRateForm $form
     * @return void
     */
    public function saveTaxRate(TaxRate $model, TaxRateForm $form): void
    {
        null!==$form->getTax_rate_name() ? $model->setTax_rate_name($form->getTax_rate_name()) : '';
        null!==$form->getTax_rate_percent() ? $model->setTax_rate_percent($form->getTax_rate_percent()) : '';
        null!==$form->getTax_rate_default() ? $model->setTax_rate_default($form->getTax_rate_default()) : '';        
        null!==$form->getPeppol_tax_rate_code() ? $model->setPeppol_tax_rate_code($form->getPeppol_tax_rate_code()) : '';        
        null!==$form->getStorecove_tax_type() ? $model->setStorecove_tax_type($form->getStorecove_tax_type()) : '';
        if ($model->isNewRecord()) {
            $model->setTax_rate_default(false);
        }
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param TaxRate $model
     * @return void
     */
    public function deleteTaxRate(TaxRate $model): void
    {
        $this->repository->delete($model);
    }
}
