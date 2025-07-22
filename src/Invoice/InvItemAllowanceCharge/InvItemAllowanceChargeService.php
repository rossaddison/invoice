<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;
use App\Invoice\InvAmount\InvAmountRepository as IAR;
use App\Invoice\InvItemAllowanceCharge\InvItemAllowanceChargeRepository as ACIIR;
use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;
use App\Invoice\InvTaxRate\InvTaxRateRepository as ITRR;
use App\Invoice\Setting\SettingRepository as SR;

final readonly class InvItemAllowanceChargeService
{
    public function __construct(private ACIIR $repository)
    {
    }

    public function saveInvItemAllowanceCharge(InvItemAllowanceCharge $model, array $array, float $vat): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int) $array['inv_id']) : '';
        isset($array['inv_item_id']) ? $model->setInv_item_id((int) $array['inv_item_id']) : '';
        isset($array['allowance_charge_id']) ? $model->setAllowance_charge_id((int) $array['allowance_charge_id']) : '';
        isset($array['amount']) ? $model->setAmount((int) $array['amount']) : '';
        $model->setVat($vat);
        $this->repository->save($model);
    }

    public function deleteInvItemAllowanceCharge(InvItemAllowanceCharge $model, IAR $iaR, IIAR $iiaR, ITRR $itrR, ACIIR $aciiR, SR $sR): void
    {
        // before deleting the allowance/charge, record its related inv_item_id so that we can update the inv_item_amount record
        $inv_item_id = $model->getInv_item_id();
        // delete the allowance / charge
        $this->repository->delete($model);
        $inv_item_amount = $iiaR->repoInvItemAmountquery($inv_item_id);
        // rebuild the accumulative totals for the inv_item_amount
        if (null !== $inv_item_amount) {
            $all_charges        = 0.00;
            $all_charges_vat    = 0.00;
            $all_allowances     = 0.00;
            $all_allowances_vat = 0.00;
            $aciis              = $aciiR->repoInvItemquery($inv_item_id);
            /** @var InvItemAllowanceCharge $acii */
            foreach ($aciis as $acii) {
                // charge add
                if ('1' == $acii->getAllowanceCharge()?->getIdentifier()) {
                    $all_charges     += (float) $acii->getAmount();
                    $all_charges_vat += (float) $acii->getVat();
                } else {
                    // allowance subtract
                    $all_allowances     += (float) $acii->getAmount();
                    $all_allowances_vat += (float) $acii->getVat();
                }
            }
            // Record the rebuilt accumulative charges and allowances totals in the InvItemAmount Entity
            $inv_item_amount->setCharge($all_charges);
            $inv_item_amount->setAllowance($all_allowances);
            $all_vat                     = $all_charges_vat - $all_allowances_vat;
            $current_item_quantity       = $inv_item_amount->getInvItem()?->getQuantity()        ?? 0.00;
            $current_item_price          = $inv_item_amount->getInvItem()?->getPrice()           ?? 0.00;
            $discount_per_item           = $inv_item_amount->getInvItem()?->getDiscount_amount() ?? 0.00;
            $quantity_price              = $current_item_quantity * $current_item_price;
            $current_discount_item_total = $current_item_quantity * $discount_per_item;
            $tax_percent                 = $inv_item_amount->getInvItem()?->getTaxRate()?->getTaxRatePercent();
            $qpIncAc                     = $quantity_price + $all_charges - $all_allowances;
            $current_tax_total           = ($qpIncAc - $current_discount_item_total) * ($tax_percent ?? 0.00) / 100.00;
            $new_tax_total               = $current_tax_total + ('0' == $sR->getSetting('enable_vat_registration') ? 0.00 : $all_vat);
            // include all item allowance charges in the subtotal
            $inv_item_amount->setSubtotal($qpIncAc);
            $inv_item_amount->setDiscount($current_discount_item_total);
            $inv_item_amount->setTax_total($new_tax_total);
            $overall_total = $qpIncAc - $current_discount_item_total + $new_tax_total;
            $inv_item_amount->setTotal($overall_total);
            $iiaR->save($inv_item_amount);
        }
    }
}
