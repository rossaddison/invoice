<?php

declare(strict_types=1);

namespace App\Invoice\Helpers\Trait;

use App\Infrastructure\Persistence\QuoteAmount\QuoteAmount;
use App\Infrastructure\Persistence\QuoteItem\QuoteItem;
use App\Infrastructure\Persistence\QuoteItemAmount\QuoteItemAmount;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;

trait QuoteCalcTrait
{
    /**
     * @param array<string, float> $totals
     */
    private function saveQuoteAmountTotals(
        int $quoteId,
        int $count,
        int $countQuoteAmount,
        QAR $qaR,
        array $totals,
    ): void {
        if ($count > 0 && $countQuoteAmount > 0) {
            $quoteAmount = $qaR->repoQuotequery($quoteId);
            if ($quoteAmount) {
                $this->setQuoteAmountFields($quoteAmount, $quoteId, $qaR, $totals);
            }
        }
        if ($count === 0 && $countQuoteAmount > 0) {
            $quoteAmount = $qaR->repoQuotequery($quoteId);
            if ($quoteAmount) {
                $quoteAmount->setQuoteId($quoteId);
                $quoteAmount->setItemSubtotal(0.00);
                $quoteAmount->setItemTaxTotal(0.00);
                $quoteAmount->setTaxTotal(0.00);
                $quoteAmount->setTotal(0.00);
                $qaR->save($quoteAmount);
            }
        }
        if ($count === 0 && $countQuoteAmount === 0) {
            $quoteAmount = new QuoteAmount();
            $quoteAmount->setQuoteId($quoteId);
            $quoteAmount->setItemSubtotal(0.00);
            $quoteAmount->setItemTaxTotal(0.00);
            $quoteAmount->setTaxTotal(0.00);
            $quoteAmount->setTotal(0.00);
            $qaR->save($quoteAmount);
        }
    }

    /**
     * @param array<string, float> $totals
     */
    private function setQuoteAmountFields(
        QuoteAmount $quoteAmount,
        int $quoteId,
        QAR $qaR,
        array $totals,
    ): void {
        $quoteAmount->setQuoteId($quoteId);
        $quoteAmount->setItemSubtotal($totals['item_subtotal'] ?: 0.00);
        $quoteAmount->setItemTaxTotal($totals['item_tax_total'] ?: 0.00);
        $quoteAmount->setPackhandleshipTotal($totals['packhandleship_total']);
        $quoteAmount->setPackhandleshipTax($totals['packhandleship_tax']);
        $quoteAmount->setTaxTotal($totals['tax_total'] ?: 0.00);
        $quoteAmount->setTotal($totals['total'] ?: 0.00);
        $qaR->save($quoteAmount);
    }

    /**
     * @param int $quote_id
     *
     * @return (float|mixed)[]
     *
     * @psalm-return array{subtotal: float|mixed,
     *                      tax_total: float|mixed,
     *                      discount: float|mixed,
     *                      charge: float|mixed,
     *                      allowance: float|mixed,
     *                      total: float|mixed}
     */
    private function quoteCalculateTotalsofItemTotals(int $quote_id,
        QIR $qiR, QIAR $qiaR): array
    {
        $get_all_items_in_quote = $qiR->repoQuoteItemIdquery($quote_id);
        $grand_sub_total = 0.00;
        $grand_taxtotal = 0.00;
        $grand_discount = 0.00;
        $grand_charge = 0.00;
        $grand_allowance = 0.00;
        $grand_total = 0.00;
        $totals = [
            'subtotal' => $grand_sub_total,
            'tax_total' => $grand_taxtotal,
            'discount' => $grand_discount,
            'charge' => $grand_charge,
            'allowance' => $grand_allowance,
            'total' => $grand_total,
        ];

        /** @var QuoteItem $item */
        foreach ($get_all_items_in_quote as $item) {
            /**
             * @psalm-suppress RawObjectIteration $item
             * @var string $key
             * @var string $value
             */
            foreach ($item as $key => $value) {
                if ($key === 'id') {
                    /**
                     * @var QuoteItemAmount $quote_item_amount
                     * @psalm-suppress RedundantCastGivenDocblockType $value
                     */
                    $quote_item_amount = $qiaR->repoQuoteItemAmountquery((int) $value);
                    $grand_sub_total  += $quote_item_amount->getSubTotal() ?? 0.00;
                    $grand_taxtotal   += $quote_item_amount->getTaxTotal() ?? 0.00;
                    $grand_charge     += $quote_item_amount->getCharge() ?? 0.00;
                    $grand_allowance  += $quote_item_amount->getAllowance() ?? 0.00;
                    $grand_discount   += $quote_item_amount->getDiscount() ?? 0.00;
                    $grand_total      += $quote_item_amount->getTotal() ?? 0.00;
                }
            }
            $totals = [
                'subtotal'  => $grand_sub_total,
                'tax_total' => $grand_taxtotal,
                'discount'  => $grand_discount,
                'charge'    => $grand_charge,
                'allowance' => $grand_allowance,
                'total'     => $grand_total,
            ];
        }
        return $totals;
    }
}
