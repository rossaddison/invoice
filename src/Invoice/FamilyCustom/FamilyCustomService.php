<?php

declare(strict_types=1);

namespace App\Invoice\FamilyCustom;

use App\Invoice\Entity\FamilyCustom;

final readonly class FamilyCustomService
{
    public function __construct(private FamilyCustomRepository $repository)
    {
    }

    /**
     * @param FamilyCustom $model
     * @param array $array
     */
    public function saveFamilyCustom(FamilyCustom $model, array $array): void
    {
        isset($array['family_id']) ? $model->setFamily_id((int) $array['family_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param FamilyCustom $model
     */
    public function deleteFamilyCustom(FamilyCustom $model): void
    {
        $this->repository->delete($model);
    }
}
