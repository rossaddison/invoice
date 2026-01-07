<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;
use App\Invoice\CustomField\CustomFieldRepository as CFR;

final readonly class CustomValueService
{
    public function __construct(
        private CustomValueRepository $repository,
        private CFR $cfR,
    ) {
    }

    /**
     * @param CustomValue $model
     * @param array $array
     */
    public function saveCustomValue(
        CustomValue $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    private function persist(
        CustomValue $model,
        array $array
    ): CustomValue {
        $custom_field = 'custom_field_id';
        if (isset($array[$custom_field])) {
            $model->setCustomField(
                $this->cfR->repoCustomFieldquery(
                    (string) $array[$custom_field]));
        }
        return $model;
    }

    /**
     * @param array|CustomValue|null $model
     */
    public function deleteCustomValue(array|CustomValue|null $model): void
    {
        $this->repository->delete($model);
    }
}
