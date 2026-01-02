<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;

final readonly class SalesOrderCustomService
{
    public function __construct(private SalesOrderCustomRepository $repository)
    {
    }

    /**
     * @param SalesOrderCustom $model
     * @param array $array
     */
    public function saveSoCustom(SalesOrderCustom $model, array $array): void
    {
        isset($array['sales_order_id']) ?
            $model->setSales_order_id((int) $array['sales_order_id']) : '';
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param array|SalesOrderCustom|null $model
     */
    public function deleteSalesOrderCustom(
                                    array|SalesOrderCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}
