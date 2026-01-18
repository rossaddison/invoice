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
        // The total of all the charges of the quote item
        $model->setCharge((float) $quoteitem['charge']);
        // The total of all the allowances of the quote item
        $model->setAllowance((float) $quoteitem['allowance']);
        $model->setSubtotal((float) $quoteitem['subtotal']);
        $model->setTax_total((float) $quoteitem['taxtotal']);
        $model->setDiscount((float) $quoteitem['discount']);
        $model->setTotal((float) $quoteitem['total']);
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
