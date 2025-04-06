<?php

declare(strict_types=1);

namespace App\Invoice\Family;

use App\Invoice\Entity\Family;

final readonly class FamilyService
{
    public function __construct(private FamilyRepository $repository)
    {
    }

    /**
     * @param Family $model
     * @param array $array
     */
    public function saveFamily(Family $model, array $array): void
    {
        isset($array['family_name']) ? $model->setFamily_name((string)$array['family_name']) : '';
        isset($array['category_primary_id']) ? $model->setCategory_primary_id((int)$array['category_primary_id']) : '';
        isset($array['category_secondary_id']) ? $model->setCategory_secondary_id((int)$array['category_secondary_id']) : '';
        $this->repository->save($model);
    }

    /**
     * @param Family $model
     */
    public function deleteFamily(Family $model): void
    {
        $this->repository->delete($model);
    }
}
