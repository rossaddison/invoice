<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderItemAmount;

use App\Invoice\Entity\SalesOrderItemAmount;

final readonly class SalesOrderItemAmountService
{
    public function __construct(private SalesOrderItemAmountRepository $repository)
    {
    }

    /**
     * Used in salesorderitemservice/saveSalesOrderItemAmount.
     */
    public function saveSalesOrderItemAmountNoForm(SalesOrderItemAmount $model, array $soitem): void
    {
        $model->setSo_item_id((int) $soitem['so_item_id']);
        /*
         * @var float $soitem['subtotal']
         * @var float $soitem['taxtotal']
         * @var float $soitem['discount']
         * @var float $soitem['total']
         */
        $model->setSubtotal($soitem['subtotal']);
        $model->setTax_total($soitem['taxtotal']);
        $model->setDiscount($soitem['discount']);
        $model->setTotal($soitem['total']);
        $this->repository->save($model);
    }

    public function deleteSalesOrderItemAmount(SalesOrderItemAmount $model): void
    {
        $this->repository->delete($model);
    }
}
