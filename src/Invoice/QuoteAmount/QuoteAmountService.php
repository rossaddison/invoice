<?php

declare(strict_types=1);

namespace App\Invoice\QuoteAmount;

use App\Invoice\Entity\QuoteAmount;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Helpers\NumberHelper;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\Quote\QuoteRepository as QR;

final readonly class QuoteAmountService
{
    public function __construct(
        private QuoteAmountRepository $repository,
        private QR $qR,
    ) {
    }

    /**
     * @param array $array
     * @param QuoteAmount $model
     * @return void
     */
    private function persist(array $array, QuoteAmount $model): void
    {
        if (isset($array['quote_id'])) {
            $quote = $this->qR->repoQuoteUnLoadedquery(
                (string) $array['quote_id']
            );
            if ($quote) {
                $model->setQuote($quote);
            }
        }
    }

    /**
     * @param QuoteAmount $model
     * @param int $quote_id
     */
    public function initializeQuoteAmount(
        QuoteAmount $model,
        int $quote_id
    ): void {
        $model->setQuoteId($quote_id);
        $model->setItemSubtotal(0.00);
        $model->setItemTaxTotal(0.00);
        $model->setPackhandleshipTotal(0.00);
        $model->setPackhandleshipTax(0.00);
        $model->setTaxTotal(0.00);
        $model->setTotal(0.00);
        $this->repository->save($model);
    }

    /**
     * @param QuoteAmount $model
     * @param QuoteAmountForm $form
     */
    public function saveQuoteAmount(
        QuoteAmount $model,
        QuoteAmountForm $form
    ): void {
        null !== $form->getQuoteId() ?
            $model->setQuoteId($form->getQuoteId()) : '';
        $model->setItemSubtotal(
            $form->getItemSubtotal() ?? 0.00
        );
        $model->setItemTaxTotal(
            $form->getItemTaxTotal() ?? 0.00
        );
        $model->setPackhandleshipTotal(
            (float) $form->getPackhandleshipTotal()
        );
        $model->setPackhandleshipTax(
            (float) $form->getPackhandleshipTax()
        );
        $model->setTaxTotal($form->getTaxTotal() ?? 0.00);
        $model->setTotal($form->getTotal() ?? 0.00);
        $this->repository->save($model);
    }

    /**
     * @param QuoteAmount $model
     * @param array $array
     */
    public function saveQuoteAmountViaCalculations(
        QuoteAmount $model,
        array $array
    ): void {
        $this->persist($array, $model);
        
        /**
         * @var int $array['quote_id']
         * @var float $array['item_subtotal']
         * @var float $array['item_taxtotal']
         * @var float $array['tax_total']
         * @var float $array['total']
         */
        $model->setQuoteId($array['quote_id']);
        $model->setItemSubtotal($array['item_subtotal']);
        $model->setItemTaxTotal($array['item_taxtotal']);
        $model->setPackhandleshipTotal(
            (float) $array['packhandleship_total']
        );
        $model->setPackhandleshipTax(
            (float) $array['packhandleship_tax']
        );
        $model->setTaxTotal($array['tax_total']);
        $model->setTotal($array['total']);
        $this->repository->save($model);
    }
    
    /**
     * Update the Quote Amounts when a quote item allowance or
     * charge is added to a quote item. Also update the Quote
     * totals using Numberhelper calculate quote_taxes function
     * Related logic: see QuoteItemAllowanceChargeController
     * functions add and edit
     * @param int $quote_id
     * @param QAR $qaR
     * @param QIAR $qiaR
     * @param QTRR $qtrR
     * @param NumberHelper $numberHelper
     */
    public function updateQuoteAmount(
        int $quote_id,
        QAR $qaR,
        QIAR $qiaR,
        QTRR $qtrR,
        NumberHelper $numberHelper
    ): void {
        $model = $this->repository->repoQuotequery(
            (string) $quote_id
        );
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
                /**
                 * @var QuoteItem $item
                 */
                foreach ($items as $item) {
                    $quoteItemId = $item->getId();
                    $quoteItemAmount = $qiaR->repoQuoteItemAmountquery(
                        $quoteItemId
                    );
                    if ($quoteItemAmount) {
                        $subtotal +=
                            $quoteItemAmount->getSubtotal() ?? 0.00;
                        $taxTotal +=
                            $quoteItemAmount->getTaxTotal() ?? 0.00;
                    }
                }
                $model->setItemSubtotal($subtotal);
                $model->setItemTaxTotal($taxTotal);
                $model->setPackhandleshipTotal($packHandleShipTotal);
                $model->setPackhandleshipTax($packHandleShipTax);
                $additionalTaxTotal =
                    $numberHelper->calculateQuoteTaxes(
                        (string) $quote_id,
                        $qtrR,
                        $qaR
                    );
                $model->setTaxTotal($additionalTaxTotal);
                $model->setTotal(
                    $subtotal + $taxTotal + $additionalTaxTotal
                );
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
