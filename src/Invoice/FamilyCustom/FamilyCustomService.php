<?php

declare(strict_types=1);

namespace App\Invoice\FamilyCustom;

use App\Invoice\Entity\FamilyCustom;
use App\Invoice\Family\FamilyRepository as FR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;

final readonly class FamilyCustomService
{
    public function __construct(
        private FamilyCustomRepository $repository,
        private FR $fR,
        private CFR $cfR,
    ) {
    }

    /**
     * @param FamilyCustom $model
     * @param array $array
     */
    public function saveFamilyCustom(
        FamilyCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['family_id']) ?
            $model->setFamily_id((int) $array['family_id']) : '';
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    private function persist(
        FamilyCustom $model,
        array $array
    ): FamilyCustom {
        $family = 'family_id';
        if (isset($array[$family])) {
            $model->setFamily(
                $this->fR->repoFamilyquery(
                    (string) $array[$family]));
        }
        $custom_field = 'custom_field_id';
        if (isset($array[$custom_field])) {
            $model->setCustomField(
                $this->cfR->repoCustomFieldquery(
                    (string) $array[$custom_field]));
        }
        return $model;
    }

    /**
     * @param FamilyCustom $model
     */
    public function deleteFamilyCustom(FamilyCustom $model): void
    {
        $this->repository->delete($model);
    }
}
