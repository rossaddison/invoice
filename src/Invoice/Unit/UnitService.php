<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;

final class UnitService
{
    private UnitRepository $repository;

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param Unit $model
     * @param array $array
     * @return void
     */
    public function saveUnit(Unit $model, array $array): void
    {
        isset($array['unit_id']) ? $model->setUnit_id((int)$array['unit_id']) : '';
        isset($array['unit_name']) ? $model->setUnit_name((string)$array['unit_name']) : '';
        isset($array['unit_name_plrl']) ? $model->setUnit_name_plrl((string)$array['unit_name_plrl']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Unit $model
     * @return void
     */
    public function deleteUnit(Unit $model): void
    {
        $this->repository->delete($model);
    }
}
