<?php

declare(strict_types=1); 

namespace App\Invoice\InvItemAllowanceCharge;

use App\Invoice\Entity\InvItemAllowanceCharge;

use App\Invoice\InvItemAmount\InvItemAmountRepository as IIAR;


final class InvItemAllowanceChargeService
{

    private InvItemAllowanceChargeRepository $repository;

    public function __construct(InvItemAllowanceChargeRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param InvItemAllowanceCharge $model
     * @param InvItemAllowanceChargeForm $form
     * @param float $vat
     * @return void
     */
    public function saveInvItemAllowanceCharge(InvItemAllowanceCharge $model, InvItemAllowanceChargeForm $form, float $vat): void
    {
        $model->nullifyRelationOnChange((int)$form->getInv_item_id(), (int)$form->getAllowance_charge_id());
        null!==$form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
        null!==$form->getInv_item_id() ? $model->setInv_item_id($form->getInv_item_id()) : '';
        null!==$form->getAllowance_charge_id() ? $model->setAllowance_charge_id($form->getAllowance_charge_id()) : '';
        null!==$form->getAmount() ? $model->setAmount($form->getAmount()) : 0.00;
        $model->setVat($vat);
        $this->repository->save($model);
    }
    
    public function deleteInvItemAllowanceCharge(InvItemAllowanceCharge $model, IIAR $iiaR): void
    {
        $inv_item_id = $model->getInv_item_id();
        $inv_item_amount = $iiaR->repoInvItemAmountquery($inv_item_id);
        if (null!==$inv_item_amount) {
            $all_charges = $inv_item_amount->getCharge() ?: 0.00;
            $all_allowances = $inv_item_amount->getAllowance() ?: 0.00;
            $old_tax_total = $inv_item_amount->getTax_total() ?: 0.00;
            $amount = (float)$model->getAmount();
            $vat = (float)$model->getVat();
            $old_total = $inv_item_amount->getTotal() ?: 0.00;
            if ($model->getAllowanceCharge()?->getIdentifier() == true) {
                 // deleting a charge will reduce the total
                 $new_total = $old_total - $amount;
                 $inv_item_amount->setCharge($all_charges-$amount);
                 $inv_item_amount->setTax_total($old_tax_total-$vat);
                 $inv_item_amount->setTotal($new_total);
            } else {
                 // deleting an allowance will increase the total
                 $new_total = $old_total + $amount;
                 $inv_item_amount->setAllowance($all_allowances-$amount);
                 $inv_item_amount->setTax_total($old_tax_total+$vat);
                 $inv_item_amount->setTotal($new_total);
            }
            $iiaR->save($inv_item_amount);
            $this->repository->delete($model);
        }
    }    
}