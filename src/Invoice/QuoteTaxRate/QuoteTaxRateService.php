<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteTaxRate;

use App\Invoice\Entity\QuoteTaxRate;

final class QuoteTaxRateService
{
    private QuoteTaxRateRepository $repository;

    public function __construct(QuoteTaxRateRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param QuoteTaxRate $model
     * @param QuoteTaxRateForm $form
     * @return void
     */
    public function saveQuoteTaxRate(QuoteTaxRate $model, QuoteTaxRateForm $form): void
    {
       null!==$form->getQuote_id() ? $model->setQuote_id($form->getQuote_id()) : '';
       null!==$form->getTax_rate_id() ? $model->setTax_rate_id($form->getTax_rate_id()) : '';
       $model->setInclude_item_tax($form->getInclude_item_tax() ?: 0);
       $model->setQuote_tax_rate_amount($form->getQuote_tax_rate_amount() ?: 0.00);
 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|QuoteTaxRate|null $model
     * @return void
     */
    public function deleteQuoteTaxRate(array|QuoteTaxRate|null $model): void
    {
        $this->repository->delete($model);
    }
}