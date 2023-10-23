<?php

declare(strict_types=1); 

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\InvAllowanceCharge\InvAllowanceChargeForm;

final class InvAllowanceChargeService
{
    private InvAllowanceChargeRepository $repository;

    public function __construct(InvAllowanceChargeRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * @param InvAllowanceCharge $model
     * @param InvAllowanceChargeForm $form
     * @return void
     */
    public function saveInvAllowanceCharge(InvAllowanceCharge $model, InvAllowanceChargeForm $form): void
    {
        null!==$form->getId() ? $model->setId($form->getId()) : '';
        null!==$form->getInv_id() ? $model->setInv_id($form->getInv_id()) : '';
        null!==$form->getAllowance_charge_id() ? $model->setAllowance_charge_id($form->getAllowance_charge_id()) : '';
        null!==$form->getAmount() ? $model->setAmount($form->getAmount()) : 0.00;
        null!==$form->getVat() ? $model->setVat($form->getVat()) : 0.00;
        $this->repository->save($model);
    }
    
    /**
     * @param InvAllowanceCharge $model
     * @return void
     */
    public function deleteInvAllowanceCharge(InvAllowanceCharge $model): void
    {
        $this->repository->delete($model);
    }
}