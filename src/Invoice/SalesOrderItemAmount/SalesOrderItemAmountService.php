<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAmount;

use App\Invoice\Entity\SalesOrderItemAmount;
use App\Invoice\SalesOrderItem\SalesOrderItemRepository as SOIR;

final readonly class SalesOrderItemAmountService
{
    public function __construct(
        private SalesOrderItemAmountRepository $repository,
        private SOIR $soiR,
    ) {
    }

    private function persist(
        SalesOrderItemAmount $model,
        array $array
    ): void {
        $sales_order_item = $this->soiR->repoSalesOrderItemquery(
            (string) $array['sales_order_item_id']
        );
        if ($sales_order_item) {
            $model->setSalesOrderItem($sales_order_item);
            $model->setSales_order_item_id(
                (int) $sales_order_item->getId()
            );
        }
    }

    /**
     * Used in salesorderitemservice/saveSalesOrderItemAmount
     * @param SalesOrderItemAmount $model
     * @param array $soitem
     */
    public function saveSalesOrderItemAmountNoForm(
        SalesOrderItemAmount $model,
        array $soitem
    ): void {
        $this->persist($model, $soitem);
        $model->setCharge((float) $soitem['charge']);
        $model->setAllowance((float) $soitem['allowance']);
        $model->setSubtotal((float) $soitem['subtotal']);
        $model->setTax_total((float) $soitem['taxtotal']);
        $model->setDiscount((float) $soitem['discount']);
        $model->setTotal((float) $soitem['total']);
        $this->repository->save($model);
    }

    /**
     * @param SalesOrderItemAmount $model
     */
    public function deleteSalesOrderItemAmount(SalesOrderItemAmount $model): void
    {
        $this->repository->delete($model);
    }
}
