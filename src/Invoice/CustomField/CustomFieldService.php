<?php

declare(strict_types=1);

namespace App\Invoice\CustomField;

use App\Invoice\Entity\CustomField;

final readonly class CustomFieldService
{
    public function __construct(private CustomFieldRepository $repository)
    {
    }

    /**
     * @param CustomField $model
     * @param array $array
     */
    public function saveCustomField(CustomField $model, array $array): void
    {
        isset($array['table']) ? $model->setTable((string)$array['table']) : '';
        isset($array['label']) ? $model->setLabel((string)$array['label']) : '';
        isset($array['type']) ? $model->setType((string)$array['type']) : '';
        isset($array['location']) ? $model->setLocation((int)$array['location']) : '';
        isset($array['order']) ? $model->setOrder((int)$array['order']) : '';
        $model->setRequired($array['required'] === '1' ? true : false);
        $this->repository->save($model);
    }

    /**
     * @param CustomField $model
     */
    public function deleteCustomField(CustomField $model): void
    {
        $this->repository->delete($model);
    }
}
