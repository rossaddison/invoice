<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteCustom;

use App\Invoice\Entity\QuoteCustom;

final class QuoteCustomService
{
    private QuoteCustomRepository $repository;

    public function __construct(QuoteCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param QuoteCustom $model
     * @param QuoteCustomForm $form
     * @return void
     */
    public function saveQuoteCustom(QuoteCustom $model, QuoteCustomForm $form): void
    { 
       null!==$form->getQuote_id() ? $model->setQuote_id($form->getQuote_id()) : '';
       null!==$form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       $model->setValue($form->getValue() ?? '');
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|QuoteCustom|null $model
     * @return void
     */
    public function deleteQuoteCustom(array|QuoteCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}