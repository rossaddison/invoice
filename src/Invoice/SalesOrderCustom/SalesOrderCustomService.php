<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;

final class SalesOrderCustomService
{
    private SalesOrderCustomRepository $repository;

    public function __construct(SalesOrderCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param SalesOrderCustom $model
     * @param array $array
     */
    public function saveSoCustom(SalesOrderCustom $model, array $array): void
    {
        isset($array['so_id']) ? $model->setSo_id((int)$array['so_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int)$array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string)$array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param array|SalesOrderCustom|null $model
     */
    public function deleteSalesOrderCustom(array|SalesOrderCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}
