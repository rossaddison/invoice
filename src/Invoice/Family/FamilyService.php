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
        isset($array['family_name']) ? $model->setFamilyName((string) $array['family_name']) : '';
        isset($array['category_primary_id']) ? $model->setCategoryPrimaryId((int) $array['category_primary_id']) : '';
        isset($array['category_secondary_id']) ? $model->setCategorySecondaryId((int) $array['category_secondary_id']) : '';
        isset($array['family_commalist']) ? $model->setFamilyCommalist((string) $array['family_commalist']) : '';
        isset($array['family_productprefix']) ? $model->setFamilyProductprefix((string) $array['family_productprefix']) : '';
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
