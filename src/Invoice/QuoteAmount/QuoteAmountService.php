<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;

final class QuoteAmountService
{

    private QuoteAmountRepository $repository;

    public function __construct(QuoteAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param QuoteAmount $model
     * @param int $quote_id
     * @return void
     */
    public function initializeQuoteAmount(QuoteAmount $model, int $quote_id) : void
    {
       $model->setQuote_id($quote_id);
       $model->setItem_subtotal(0.00);
       $model->setItem_tax_total(0.00);
       $model->setTax_total(0.00);
       $model->setTotal(0.00); 
       $this->repository->save($model);
    }

    /**
     * 
     * @param QuoteAmount $model
     * @param string $basis_quote_id
     * @param string|null $new_quote_id
     * @return void
     */
    public function initializeCopyQuoteAmount(QuoteAmount $model, string $basis_quote_id, string|null $new_quote_id) : void
    {
       $basis_quote = $this->repository->repoQuotequery($basis_quote_id);
       if ($basis_quote) {
        $model->setQuote_id((int)$new_quote_id);
        $model->setItem_subtotal($basis_quote->getItem_subtotal() ?? 0.00);
        $model->setItem_tax_total($basis_quote->getItem_tax_total() ?? 0.00);
        $model->setTax_total($basis_quote->getTax_total() ?? 0.00);
        $model->setTotal($basis_quote->getTotal() ?? 0.00); 
        $this->repository->save($model);
       } 
    } 
    
    /**
     * 
     * @param QuoteAmount $model
     * @param QuoteAmountForm $form
     * @return void
     */
    public function saveQuoteAmount(QuoteAmount $model, QuoteAmountForm $form): void
    {        
       null!==$form->getQuote_id() ? $model->setQuote_id($form->getQuote_id()) : '';
       $model->setItem_subtotal($form->getItem_subtotal() ?? 0.00);
       $model->setItem_tax_total($form->getItem_tax_total() ?? 0.00);
       $model->setTax_total($form->getTax_total() ?? 0.00);
       $model->setTotal($form->getTotal() ?? 0.00); 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param QuoteAmount $model
     * @param array $array
     * @return void
     */
    public function saveQuoteAmountViaCalculations(QuoteAmount $model, array $array): void
    {        
       /** 
        * @var int $array['quote_id']
        * @var float $array['item_subtotal']
        * @var float $array['item_taxtotal']
        * @var float $array['tax_total']
        * @var float $array['total']
        */
       $model->setQuote_id($array['quote_id']);
       $model->setItem_subtotal($array['item_subtotal']);
       $model->setItem_tax_total($array['item_taxtotal']);
       $model->setTax_total($array['tax_total']);
       $model->setTotal($array['total']);
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param QuoteAmount|null $model
     * @return void
     */
    public function deleteQuoteAmount(QuoteAmount|null $model): void
    {
        $this->repository->delete($model);
    }
}