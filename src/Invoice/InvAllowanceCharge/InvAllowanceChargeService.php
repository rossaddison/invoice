<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Invoice\Entity\InvAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;

final readonly class InvAllowanceChargeService
{
    public function __construct(private InvAllowanceChargeRepository $repository, private ACR $acR) {}

    /**
     * @param InvAllowanceCharge $model
     * @param array $array
     */
    public function saveInvAllowanceCharge(InvAllowanceCharge $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['allowance_charge_id']);
        isset($array['id']) ? $model->setId((int) $array['id']) : '';
        isset($array['inv_id']) ? $model->setInv_id((int) $array['inv_id']) : '';
        isset($array['allowance_charge_id']) ? $model->setAllowance_charge_id((int) $array['allowance_charge_id']) : '';
        isset($array['amount']) ? $model->setAmount((float) $array['amount']) : 0.00;
        $allowance_charge = $this->acR->repoAllowanceChargequery((string) $array['allowance_charge_id']);
        if (null !== $allowance_charge && null !== $allowance_charge->getTaxRate()) {
            $allowanceChargeTaxRate = $allowance_charge->getTaxRate();
            if (null !== $allowanceChargeTaxRate) {
                if ($array['amount'] == '') {
                    $amount = 0.00;
                } else {
                    $amount = (float) $array['amount'];
                }
                $vat = $amount * ($allowanceChargeTaxRate->getTaxRatePercent() ?? 0.00) / 100.00;
                $model->setVat($vat);
            }
        }
        $this->repository->save($model);
    }

    /**
     * @param InvAllowanceCharge $model
     */
    public function deleteInvAllowanceCharge(InvAllowanceCharge $model): void
    {
        $this->repository->delete($model);
    }
}
