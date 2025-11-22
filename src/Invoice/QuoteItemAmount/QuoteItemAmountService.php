<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;

final readonly class QuoteItemAmountService
{
    public function __construct(private QuoteItemAmountRepository $repository)
    {
    }

    /**
     * @param QuoteItemAmount $model
     * @param array $quoteitem
     */
    public function saveQuoteItemAmountNoForm(QuoteItemAmount $model, array $quoteitem): void
    {
        $model->setQuote_item_id((int) $quoteitem['quote_item_id']);
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
     * @param QuoteItemAmount $model
     */
    public function deleteQuoteItemAmount(QuoteItemAmount $model): void
    {
        $this->repository->delete($model);
    }
}
