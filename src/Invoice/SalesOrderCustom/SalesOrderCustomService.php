<?php

declare(strict_types=1);

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;

final readonly class SalesOrderCustomService
{
    public function __construct(private SalesOrderCustomRepository $repository)
    {
    }

    public function saveSoCustom(SalesOrderCustom $model, array $array): void
    {
        isset($array['so_id']) ? $model->setSo_id((int) $array['so_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    public function deleteSalesOrderCustom(array|SalesOrderCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}
