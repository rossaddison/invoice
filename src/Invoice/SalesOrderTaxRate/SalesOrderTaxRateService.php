<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;

final readonly class SalesOrderTaxRateService
{
    public function __construct(private SalesOrderTaxRateRepository $repository)
    {
    }

    /**
     * @param SalesOrderTaxRate $model
     * @param array $array
     */
    public function saveSoTaxRate(SalesOrderTaxRate $model, array $array): void
    {
        isset($array['sales_order_id']) ? $model->setSales_order_id((int) $array['sales_order_id']) : '';
        isset($array['tax_rate_id']) ? $model->setTax_rate_id((int) $array['tax_rate_id']) : '';
        $model->setInclude_item_tax((int) $array['include_item_tax'] ?: 0);
        $model->setSales_order_tax_rate_amount((float) $array['sales_order_tax_rate_amount'] ?: 0.00);

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
