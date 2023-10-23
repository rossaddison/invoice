<?php

declare(strict_types=1); 

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\QuoteItemAmount\QuoteItemAmountForm;

final class QuoteItemAmountService
{
    private QuoteItemAmountRepository $repository;

    public function __construct(QuoteItemAmountRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param QuoteItemAmount $model
     * @param QuoteItemAmountForm $form
     * @return void
     */
    public function saveQuoteItemAmount(QuoteItemAmount $model, QuoteItemAmountForm $form): void
    { 
       null!==$form->getQuote_item_id() ? $model->setQuote_item_id($form->getQuote_item_id()) : '';
       $model->setSubtotal($form->getSubtotal() ?? 0.00);
       $model->setTax_total($form->getTax_total() ?? 0.00);
       $model->setDiscount($form->getDiscount() ?? 0.00);
       $model->setTotal($form->getTotal() ?? 0.00);
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param QuoteItemAmount $model
     * @param array $quoteitem
     * @return void
     */
    public function saveQuoteItemAmountNoForm(QuoteItemAmount $model, array $quoteitem): void
    {        
       $model->setQuote_item_id((int)$quoteitem['quote_item_id']);
       /** 
        * @var float $quoteitem['subtotal']
        * @var float $quoteitem['taxtotal']
        * @var float $quoteitem['discount']
        * @var float $quoteitem['total']
        */
       $model->setSubtotal($quoteitem['subtotal']);
       $model->setTax_total($quoteitem['taxtotal']);
       $model->setDiscount($quoteitem['discount']);
       $model->setTotal($quoteitem['total']); 
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param QuoteItemAmount $model
     * @return void
     */
    public function deleteQuoteItemAmount(QuoteItemAmount $model): void
    {
       $this->repository->delete($model);
    }
}