<?php

declare(strict_types=1);

namespace App\Invoice\Quote;

use App\Invoice\Entity\Quote;
use App\Invoice\Entity\QuoteAllowanceCharge;
use App\Invoice\Entity\QuoteCustom;
use App\Invoice\Entity\QuoteItem;
use App\Invoice\Entity\QuoteItemAllowanceCharge;
use App\Invoice\Entity\QuoteTaxRate;
use App\Invoice\QuoteAllowanceCharge\QuoteAllowanceChargeRepository as ACQR;
use App\Invoice\QuoteItemAllowanceCharge\QuoteItemAllowanceChargeRepository as ACQIR;
use App\Invoice\QuoteAmount\QuoteAmountRepository as QAR;
use App\Invoice\QuoteItemAmount\QuoteItemAmountRepository as QIAR;
use App\Invoice\QuoteCustom\QuoteCustomRepository as QCR;
use App\Invoice\QuoteItem\QuoteItemRepository as QIR;
use App\Invoice\QuoteTaxRate\QuoteTaxRateRepository as QTRR;
use App\Invoice\QuoteAmount\QuoteAmountService as QAS;
use App\Invoice\QuoteCustom\QuoteCustomService as QCS;
use App\Invoice\QuoteItem\QuoteItemService as QIS;
use App\Invoice\QuoteTaxRate\QuoteTaxRateService as QTRS;

final class QuoteDeletionService
{
    public function __construct(
        private QIS $itemService,
        private QIR $itemRepo,
        private QIAR $itemAmountRepo,
        private ACQIR $itemAllowanceRepo,
        private QAR $amountRepo,
        private QAS $amountService,
        private QTRR $taxRepo,
        private QTRS $taxService,
        private QCR $customRepo,
        private QCS $customService,
        private ACQR $allowanceRepo,
    ) {}

    public function delete(Quote $quote): void
    {
        $quoteId = $quote->getId();
        if (null==$quoteId) {
            return;
        }
        
        $this->deleteItems($quoteId);
        $this->deleteQuoteAmount($quoteId);
        $this->deleteTaxes($quoteId);
        $this->deleteCustomFields($quoteId);
        $this->deleteAllowances($quoteId);
    }

    private function deleteItems(string $quoteId): void
    {
        /** @var QuoteItem $item */
        foreach ($this->itemRepo->repoQuoteItemIdquery($quoteId) as $item) {
            $itemId = $item->getId();
            
            if (null!== ($amount = $this->itemAmountRepo->repoQuoteItemAmountquery($itemId))) {
                $this->itemAmountRepo->delete($amount);
            }

            /** @var QuoteItemAllowanceCharge $qiac */
            foreach ($this->itemAllowanceRepo->repoQuoteItemquery($itemId) as $qiac) {
                $this->itemAllowanceRepo->delete($qiac);
            }

            $this->itemService->deleteQuoteItem($item);
        }
    }

    private function deleteQuoteAmount(string $quoteId): void
    {
        if ($this->amountRepo->repoQuoteAmountCount($quoteId) > 0) {
            $amount = $this->amountRepo->repoQuotequery($quoteId);
            if ($amount) {
                $this->amountService->deleteQuoteAmount($amount);
            }
        }
    }

    private function deleteTaxes(string $quoteId): void
    {
        /** @var QuoteTaxRate $qtr */
        foreach ($this->taxRepo->repoQuotequery($quoteId) as $qtr) {
            $this->taxService->deleteQuoteTaxRate($qtr);
        }
    }

    private function deleteCustomFields(string $quoteId): void
    {
        /** @var QuoteCustom $qc */
        foreach ($this->customRepo->repoFields($quoteId) as $qc) {
            $this->customService->deleteQuoteCustom($qc);
        }
    }

    private function deleteAllowances(string $quoteId): void
    {
        /** @var QuoteAllowanceCharge $qac */
        foreach ($this->allowanceRepo->repoACQquery($quoteId) as $qac) {
            $this->allowanceRepo->delete($qac);
        }
    }
}
