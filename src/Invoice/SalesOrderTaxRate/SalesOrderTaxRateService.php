<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;

final class SalesOrderTaxRateService
{
    private SalesOrderTaxRateRepository $repository;

    public function __construct(SalesOrderTaxRateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param SalesOrderTaxRate $model
     * @param SalesOrderTaxRateForm $form
     * @return void
     */
    public function saveSoTaxRate(SalesOrderTaxRate $model, SalesOrderTaxRateForm $form): void
    {
       null!==$form->getSo_id() ? $model->setSo_id($form->getSo_id()) : '';
       null!==$form->getTax_rate_id() ? $model->setTax_rate_id($form->getTax_rate_id()) : '';
       $model->setInclude_item_tax($form->getInclude_item_tax() ?: 0);
       $model->setSo_tax_rate_amount($form->getSo_tax_rate_amount() ?: 0.00);
 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|SalesOrderTaxRate|null $model
     * @return void
     */
    public function deleteSalesOrderTaxRate(array|SalesOrderTaxRate|null $model): void
    {
        $this->repository->delete($model);
    }
}