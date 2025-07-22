<?php

declare(strict_types=1);

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;

final readonly class CustomValueService
{
    public function __construct(private CustomValueRepository $repository)
    {
    }

    public function saveCustomValue(CustomValue $model, array $array): void
    {
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    public function deleteCustomValue(array|CustomValue|null $model): void
    {
        $this->repository->delete($model);
    }
}
