<?php
declare(strict_types=1); 

namespace App\Invoice\AllowanceCharge;

use App\Invoice\Entity\AllowanceCharge;

final class AllowanceChargeService
{
    private AllowanceChargeRepository $repository;

    public function __construct(AllowanceChargeRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveAllowanceCharge(AllowanceCharge $model, AllowanceChargeForm $form): void
    {
        null!==$form->getIdentifier() ? $model->setIdentifier($form->getIdentifier()) : '';
        null!==$form->getReason_code() ? $model->setReason_code($form->getReason_code()) : '';
        null!==$form->getReason() ? $model->setReason($form->getReason()) : '';
        null!==$form->getMultiplier_factor_numeric() ? $model->setMultiplier_factor_numeric($form->getMultiplier_factor_numeric()) : '';
        null!==$form->getAmount() ? $model->setAmount($form->getAmount()) : '';
        null!==$form->getBase_amount() ? $model->setBase_amount($form->getBase_amount()) : '';
        null!==$form->getTax_rate_id() ? $model->setTax_rate_id($form->getTax_rate_id()) : '';
        $this->repository->save($model);
    }
    
    public function deleteAllowanceCharge(AllowanceCharge $model): void
    {
        $this->repository->delete($model);
    }
}