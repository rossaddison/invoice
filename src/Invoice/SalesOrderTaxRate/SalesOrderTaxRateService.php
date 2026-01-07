<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;
use App\Invoice\SalesOrder\SalesOrderRepository as SOR;
use App\Invoice\TaxRate\TaxRateRepository as TRR;

final readonly class SalesOrderTaxRateService
{
    public function __construct(
        private SalesOrderTaxRateRepository $repository,
        private SOR $soR,
        private TRR $trR,
    ) {
    }

    private function persist(
        SalesOrderTaxRate $model,
        array $array
    ): void {
        $sales_order = $this->soR->repoSalesOrderUnLoadedquery(
            (string) $array['sales_order_id']
        );
        if ($sales_order) {
            $model->setSalesOrder($sales_order);
            $model->setSales_order_id((int) $sales_order->getId());
        }
        $tax_rate = $this->trR->repoTaxRatequery(
            (string) $array['tax_rate_id']
        );
        if ($tax_rate) {
            $model->setTaxRate($tax_rate);
            $model->setTax_rate_id((int) $tax_rate->getTaxRateId());
        }
    }

    /**
     * @param SalesOrderTaxRate $model
     * @param array $array
     */
    public function saveSoTaxRate(
        SalesOrderTaxRate $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $model->setInclude_item_tax(
            (int) $array['include_item_tax'] ?: 0
        );
        $model->setSales_order_tax_rate_amount(
            (float) $array['sales_order_tax_rate_amount'] ?: 0.00
        );
        $this->repository->save($model);
    }

    /**
     * @param array|SalesOrderTaxRate|null $model
     */
    public function deleteSalesOrderTaxRate(array|SalesOrderTaxRate|null $model): void
    {
        $this->repository->delete($model);
    }
}
