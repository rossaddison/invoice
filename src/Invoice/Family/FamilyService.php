<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;

final class FamilyService
{
    private FamilyRepository $repository;

    public function __construct(FamilyRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     *
     * @param Family $model
     * @param array $array
     * @return void
     */
    public function saveFamily(Family $model, array $array): void
    {
        isset($array['family_name']) ? $model->setFamily_name((string)$array['family_name']) : '';
        $this->repository->save($model);
    }

    /**
     *
     * @param Family $model
     * @return void
     */
    public function deleteFamily(Family $model): void
    {
        $this->repository->delete($model);
    }
}
