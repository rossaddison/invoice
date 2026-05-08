<?php

declare(strict_types=1);

namespace App\Invoice\Inv;

use App\Infrastructure\Persistence\Inv\Inv;
use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Infrastructure\Persistence\InvCustom\InvCustom;
use App\Infrastructure\Persistence\InvItem\InvItem;
use App\Infrastructure\Persistence\InvItemAllowanceCharge\InvItemAllowanceCharge;
use App\Infrastructure\Persistence\InvTaxRate\InvTaxRate;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeRepository as ACIR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvCustom\InvCustomRepository as ICR;
use App\Invoice\InvItem\InvItemRepository as IIR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\InvAmount\InvAmountService as IAS;
use App\Invoice\InvCustom\InvCustomService as ICS;
use App\Invoice\InvItem\InvItemService as IIS;
use App\Invoice\InvTaxRate\InvTaxRateService as ITRS;

final class InvDeletionService
{
    public function __construct(
        private IIS $itemService,
        private IIR $itemRepo,
        private IIAR $itemAmountRepo,
        private ACIIR $itemAllowanceRepo,
        private IAR $amountRepo,
        private IAS $amountService,
        private ITRR $taxRepo,
        private ITRS $taxService,
        private ICR $customRepo,
        private ICS $customService,
        private ACIR $allowanceRepo,
    ) {}

    public function delete(Inv $inv): void
    {
        $invId = $inv->reqId();
        if ($invId == 0) {
            return;
        }
        
        $this->deleteItems($invId);
        $this->deleteInvAmount($invId);
        $this->deleteTaxes($invId);
        $this->deleteCustomFields($invId);
        $this->deleteAllowances($invId);
    }

    private function deleteItems(int $invId): void
    {
        /** @var InvItem $item */
        foreach ($this->itemRepo->repoInvItemIdquery($invId) as $item) {
            $itemId = $item->reqId();
            
            if (null!== ($amount = $this->itemAmountRepo->repoInvItemAmountquery($itemId))) {
                $this->itemAmountRepo->delete($amount);
            }

            /** @var InvItemAllowanceCharge $iiac */
            foreach ($this->itemAllowanceRepo->repoInvItemquery($itemId) as $iiac) {
                $this->itemAllowanceRepo->delete($iiac);
            }

            $this->itemService->deleteInvItem($item);
        }
    }

    private function deleteInvAmount(int $invId): void
    {
        if ($this->amountRepo->repoInvAmountCount($invId) > 0) {
            $amount = $this->amountRepo->repoInvquery($invId);
            if ($amount) {
                $this->amountService->deleteInvAmount($amount);
            }
        }
    }

    private function deleteTaxes(int $invId): void
    {
        /** @var InvTaxRate $itr */
        foreach ($this->taxRepo->repoInvquery($invId) as $itr) {
            $this->taxService->deleteInvTaxRate($itr);
        }
    }

    private function deleteCustomFields(int $invId): void
    {
        /** @var InvCustom $ic */
        foreach ($this->customRepo->repoFields($invId) as $ic) {
            $this->customService->deleteInvCustom($ic);
        }
    }

    private function deleteAllowances(int $invId): void
    {
        /** @var InvAllowanceCharge $iac */
        foreach ($this->allowanceRepo->repoACIquery($invId) as $iac) {
            $this->allowanceRepo->delete($iac);
        }
    }
}
