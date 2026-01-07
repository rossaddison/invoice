<?php

declare(strict_types=1);

namespace App\Invoice\QuoteItemAmount;

use App\Invoice\Entity\QuoteItemAmount;
use App\Invoice\QuoteItem\QuoteItemRepository;

final readonly class QuoteItemAmountService
{
    public function __construct(
        private QuoteItemAmountRepository $repository,
        private QuoteItemRepository $quoteItemRepository,
    ) {
    }

    private function persist(
        QuoteItemAmount $model,
        array $quoteitem
    ): void {
        $quote_item = $this->quoteItemRepository
            ->repoQuoteItemquery(
                (string) $quoteitem['quote_item_id']
            );
        if ($quote_item) {
            $model->setQuoteItem($quote_item);
        }
    }

    /**
     * @param QuoteItemAmount $model
     * @param array $quoteitem
     */
    public function saveQuoteItemAmountNoForm(
        QuoteItemAmount $model,
        array $quoteitem
    ): void {
        $this->persist($model, $quoteitem);
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
    public function deleteQuoteItemAmount(
        QuoteItemAmount $model
    ): void {
        $this->repository->delete($model);
    }
}
