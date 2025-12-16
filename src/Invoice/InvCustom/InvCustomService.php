<?php

declare(strict_types=1);

namespace App\Invoice\InvCustom;

use App\Invoice\Entity\InvCustom;

final readonly class InvCustomService
{
    public function __construct(private InvCustomRepository $repository)
    {
    }

    /**
     * @param InvCustom $model
     * @param array $array
     */
    public function saveInvCustom(InvCustom $model, array $array): void
    {
        isset($array['inv_id']) ? $model->setInv_id((int) $array['inv_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param InvCustom $model
     */
    public function deleteInvCustom(InvCustom $model): void
    {
        $this->repository->delete($model);
    }
}
