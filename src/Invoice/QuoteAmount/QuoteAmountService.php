<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;

final readonly class QuoteAmountService
{
    public function __construct(private QuoteAmountRepository $repository)
    {
    }

    /**
     * @param QuoteAmount $model
     * @param int $quote_id
     */
    public function initializeQuoteAmount(QuoteAmount $model, int $quote_id): void
    {
        $model->setQuote_id($quote_id);
        $model->setItem_subtotal(0.00);
        $model->setItem_tax_total(0.00);
        $model->setPackhandleship_total(0.00);
        $model->setPackhandleship_tax(0.00);
        $model->setTax_total(0.00);
        $model->setTotal(0.00);
        $this->repository->save($model);
    }

    /**
     * @param QuoteAmount $model
     * @param QuoteAmountForm $form
     */
    public function saveQuoteAmount(QuoteAmount $model, QuoteAmountForm $form): void
    {
        null !== $form->getQuote_id() ? $model->setQuote_id($form->getQuote_id()) : '';
        $model->setItem_subtotal($form->getItem_subtotal() ?? 0.00);
        $model->setItem_tax_total($form->getItem_tax_total() ?? 0.00);
        $model->setPackhandleship_total((float) $form->getPackhandleship_total());
        $model->setPackhandleship_tax((float) $form->getPackhandleship_tax());
        $model->setTax_total($form->getTax_total() ?? 0.00);
        $model->setTotal($form->getTotal() ?? 0.00);
        $this->repository->save($model);
    }

    /**
     * @param QuoteAmount $model
     * @param array $array
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
        $model->setPackhandleship_total((float) $array['packhandleship_total']);
        $model->setPackhandleship_tax((float) $array['packhandleship_tax']);
        $model->setTax_total($array['tax_total']);
        $model->setTotal($array['total']);
        $this->repository->save($model);
    }
    
    /**
     * Update the Quote Amounts when a quote item allowance or charge is added
     * to a quote item. Also update the Quote totals using Numberhelper
     * calculate quote_taxes function
     * Related logic: see QuoteItemAllowanceChargeController functions add and
     * edit
     * @param int $quote_id
     * @param QAR $qaR
     * @param QIAR $qiaR
     * @param QTRR $qtrR
     * @param NumberHelper $numberHelper
     */
    public function updateQuoteAmount(int $quote_id, QAR $qaR, QIAR $qiaR,
        QTRR $qtrR, NumberHelper $numberHelper): void
    {
        $model = $this->repository->repoQuotequery((string) $quote_id);
        if (null !== $model) {
            $quote = $model->getQuote();
            if (null !== $quote) {
                /**
                 * Related logic: see Entity\Quote
                 * #[HasMany(target: QuoteItem::class)]
                 * private ArrayCollection $items;
                 */
                $items = $quote->getItems();
                $subtotal = 0.00;
                $packHandleShipTotal = 0.00;
                $packHandleShipTax = 0.00;
                $taxTotal = 0.00;
                $discount = 0.00;
                $charge = 0.00;
                $allowance = 0.00;
                /**
                 * @var QuoteItem $item
                 */
                foreach ($items as $item) {
                    $quoteItemId = $item->getId();
                    $quoteItemAmount = $qiaR->repoQuoteItemAmountquery(
                        $quoteItemId);
                    if ($quoteItemAmount) {
                        $subtotal += $quoteItemAmount->getSubtotal() ?? 0.00;
                        $taxTotal += $quoteItemAmount->getTax_total() ?? 0.00;
                        $discount += $quoteItemAmount->getDiscount() ?? 0.00;
                        $charge += $quoteItemAmount->getCharge() ?? 0.00;
                        $allowance += $quoteItemAmount->getAllowance() ?? 0.00;
                    }
                }
                $model->setItem_subtotal($subtotal);
                $model->setItem_tax_total($taxTotal);
                $model->setPackhandleship_total($packHandleShipTotal);
                $model->setPackhandleship_tax($packHandleShipTax);
                $additionalTaxTotal = $numberHelper->calculate_quote_taxes(
                    (string) $quote_id, $qtrR, $qaR);
                $model->setTax_total($additionalTaxTotal);
                $model->setTotal($subtotal + $taxTotal + $additionalTaxTotal);
                $this->repository->save($model);
            }
        }
    }

    /**
     * @param QuoteAmount|null $model
     */
    public function deleteQuoteAmount(?QuoteAmount $model): void
    {
        $this->repository->delete($model);
    }
}
