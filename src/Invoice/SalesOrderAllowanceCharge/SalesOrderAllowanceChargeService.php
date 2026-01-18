<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderAllowanceCharge;

use App\Invoice\Entity\SalesOrderAllowanceCharge;
use App\Invoice\AllowanceCharge\AllowanceChargeRepository as ACR;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;

final readonly class SalesOrderAllowanceChargeService
{
    public function __construct(
        private SalesOrderAllowanceChargeRepository $repository,
        private ACR $acR,
        private SOR $soR,
    ) {
    }

    private function persist(
        SalesOrderAllowanceCharge $model,
        array $array
    ): void {
        $allowance_charge = $this->acR->repoAllowanceChargequery(
            (string) $array['allowance_charge_id']
        );
        if ($allowance_charge) {
            $model->setAllowanceCharge($allowance_charge);
            $model->setAllowance_charge_id(
                (int) $allowance_charge->getId()
            );
        }
        $sales_order = $this->soR->repoSalesOrderUnLoadedquery(
            (string) $array['sales_order_id']
        );
        if ($sales_order) {
            $model->setSalesOrder($sales_order);
            $model->setSales_order_id((int) $sales_order->getId());
        }
    }

    /**
     * @param SalesOrderAllowanceCharge $model
     * @param array $array
     */
    public function saveSalesOrderAllowanceCharge(
        SalesOrderAllowanceCharge $model,
        array $array
    ): void {
        isset($array['id']) ? $model->setId((int) $array['id']) : '';
        $this->persist($model, $array);
        isset($array['amount'])
            ? $model->setAmount((float) $array['amount'])
            : 0.00;
        $acId = (string) $array['allowance_charge_id'];
        $ac = $this->acR->repoAllowanceChargequery($acId);
        if (null !== $ac && null !== $ac->getTaxRate()
        ) {
            $allowanceChargeTaxRate = $ac->getTaxRate();
            if (null !== $allowanceChargeTaxRate) {
                if ($array['amount'] == '') {
                    $amount = 0.00;
                } else {
                    $amount = (float) $array['amount'];
                }
    $taxation = $amount * ($allowanceChargeTaxRate->getTaxRatePercent() ?? 0.00)
            / 100.00;
                $model->setVatOrTax($taxation);
            }
        }
        $this->repository->save($model);
    }

    /**
     * @param SalesOrderAllowanceCharge $model
     */
    public function deleteSalesOrderAllowanceCharge(
        SalesOrderAllowanceCharge $model
    ): void {
        $this->repository->delete($model);
    }
}
