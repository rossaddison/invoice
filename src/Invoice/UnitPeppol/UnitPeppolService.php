<?php

declare(strict_types=1);

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;

final class UnitPeppolService
{
    private UnitPeppolRepository $repository;

    public function __construct(UnitPeppolRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveUnitPeppol(UnitPeppol $model, array $array): void
    {
        isset($array['id']) ? $model->setId((int)$array['id']) : '';
        isset($array['unit_id']) ? $model->setUnit_id((int)$array['unit_id']) : '';
        isset($array['code']) ? $model->setCode((string)$array['code']) : '';
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        isset($array['description']) ? $model->setDescription((string)$array['description']) : '';
        $this->repository->save($model);
    }

    public function deleteUnitPeppol(UnitPeppol $model): void
    {
        $this->repository->delete($model);
    }
}
