<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Invoice\Entity\SalesOrderAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;

final readonly class SalesOrderAllowanceChargeService
{
    public function __construct(
    private SalesOrderAllowanceChargeRepository $repository, private ACR $acR)
    {
    }

    /**
     * @param SalesOrderAllowanceCharge $model
     * @param array $array
     */
    public function saveSalesOrderAllowanceCharge(
        SalesOrderAllowanceCharge $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['allowance_charge_id'], (int) $array['sales_order_id']);
        isset($array['id']) ? $model->setId((int) $array['id']) : '';
        isset($array['sales_order_id']) ?
            $model->setSales_order_id((int) $array['sales_order_id']) : '';
        isset($array['allowance_charge_id']) ?
            $model->setAllowance_charge_id((int) $array['allowance_charge_id'])
                : '';
        isset($array['amount']) ? $model->setAmount((float) $array['amount'])
                : 0.00;
        $allowance_charge = $this->acR->repoAllowanceChargequery(
            (string) $array['allowance_charge_id']);
        if (null !== $allowance_charge
            && null !== $allowance_charge->getTaxRate()) {
            $allowanceChargeTaxRate = $allowance_charge->getTaxRate();
            if (null !== $allowanceChargeTaxRate) {
                if ($array['amount'] == '') {
                    $amount = 0.00;
                } else {
                    $amount = (float) $array['amount'];
                }
                $vatOrTax = $amount
                * ($allowanceChargeTaxRate->getTaxRatePercent() ?? 0.00)/100.00;
                $model->setVatOrTax($vatOrTax);
            }
        }
        $this->repository->save($model);
    }

    /**
     * @param SalesOrderAllowanceCharge $model
     */
    public function deleteSalesOrderAllowanceCharge(
            SalesOrderAllowanceCharge $model): void
    {
        $this->repository->delete($model);
    }
}
