<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderTaxRate;

use App\Invoice\Entity\SalesOrderTaxRate;

final class SalesOrderTaxRateService
{
    private SalesOrderTaxRateRepository $repository;

    public function __construct(SalesOrderTaxRateRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param SalesOrderTaxRate $model
     * @param array $array
     */
    public function saveSoTaxRate(SalesOrderTaxRate $model, array $array): void
    {
        isset($array['so_id']) ? $model->setSo_id((int)$array['so_id']) : '';
        isset($array['tax_rate_id']) ? $model->setTax_rate_id((int)$array['tax_rate_id']) : '';
        $model->setInclude_item_tax((int)$array['include_item_tax'] ?: 0);
        $model->setSo_tax_rate_amount((float)$array['so_tax_rate_amount'] ?: 0.00);

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
