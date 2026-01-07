<?php

declare(strict_types=1);

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;
use App\Invoice\Inv\InvRepository as IR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;

final readonly class InvCustomService
{
    public function __construct(
        private InvCustomRepository $repository,
        private IR $iR,
        private CFR $cfR,
    ) {
    }

    /**
     * @param InvCustom $model
     * @param array $array
     */
    public function saveInvCustom(
        InvCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['inv_id']) ?
            $model->setInv_id((int) $array['inv_id']) : '';
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    private function persist(
        InvCustom $model,
        array $array
    ): InvCustom {
        $inv = 'inv_id';
        if (isset($array[$inv])) {
            $invEntity = $this->iR->repoInvUnLoadedquery(
                (string) $array[$inv]);
            if ($invEntity) {
                $model->setInv($invEntity);
            }
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
     * @param InvCustom $model
     */
    public function deleteInvCustom(InvCustom $model): void
    {
        $this->repository->delete($model);
    }
}
