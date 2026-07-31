<?php

declare(strict_types=1);

namespace App\Invoice\InvAllowanceCharge;

use App\Infrastructure\Persistence\InvAllowanceCharge\InvAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;

final readonly class InvAllowanceChargeService
{
    public function __construct(private InvAllowanceChargeRepository $repository, private ACR $acR)
    {
    }

    /**
     * @param InvAllowanceCharge $model
     * @param array $array
     */
    public function saveInvAllowanceCharge(InvAllowanceCharge $model, array $array): void
    {
        isset($array['id']) ? $model->setId((int) $array['id']) : '';
        isset($array['inv_id']) ? $model->setInvId((int) $array['inv_id']) : '';
        isset($array['allowance_charge_id']) ?
            $model->setAllowanceChargeId((int) $array['allowance_charge_id']) : '';
        isset($array['amount']) ? $model->setAmount((float) $array['amount']) : 0.00;
        $allowance_charge = $this->acR->repoAllowanceChargequery((int) $array['allowance_charge_id']);
        if (null !== $allowance_charge && null !== $allowance_charge->getTaxRate()) {
            $allowanceChargeTaxRate = $allowance_charge->getTaxRate();
            if (null !== $allowanceChargeTaxRate) {
                if ($array['amount'] == '') {
                    $amount = 0.00;
                } else {
                    $amount = (float) $array['amount'];
                }
                $vatOrTax = $amount * ($allowanceChargeTaxRate->getTaxRatePercent() ?? 0.00) / 100.00;
                $model->setVatOrTax($vatOrTax);
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

    /**
     * Reverses the basis invoice's document-level allowance/charges onto a
     * credit note, matching the negated items/amounts/tax rates applied
     * elsewhere in that flow. Amount is negated directly rather than via the
     * form (InvAllowanceChargeForm enforces amount > 0); vat_or_tax follows
     * automatically since saveInvAllowanceCharge derives it from amount.
     * @param int $basis_inv_id
     * @param int $new_inv_id
     */
    public function initializeCreditInvAllowanceCharges(int $basis_inv_id, int $new_inv_id): void
    {
        $inv_allowance_charges = $this->repository->repoACIquery($basis_inv_id);
        /**
         * @var InvAllowanceCharge $inv_allowance_charge
         */
        foreach ($inv_allowance_charges as $inv_allowance_charge) {
            $this->saveInvAllowanceCharge(new InvAllowanceCharge(), [
                'inv_id' => $new_inv_id,
                'allowance_charge_id' => $inv_allowance_charge->reqAllowanceChargeId(),
                'amount' => (float) $inv_allowance_charge->getAmount() * -1.00,
            ]);
        }
    }
}
