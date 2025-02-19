<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;

final readonly class ClientCustomService
{
    public function __construct(private ClientCustomRepository $repository)
    {
    }

    /**
     * @param ClientCustom $model
     * @param array $array
     */
    public function saveClientCustom(ClientCustom $model, array $array): void
    {
        isset($array['client_id']) ? $model->setClient_id((int)$array['client_id']) : '';
        isset($array['custom_field_id']) ? $model->setCustom_field_id((int)$array['custom_field_id']) : '';
        isset($array['value']) ? $model->setValue((string)$array['value']) : '';
        $this->repository->save($model);
    }

    /**
     * @param ClientCustom $model
     */
    public function deleteClientCustom(ClientCustom $model): void
    {
        $this->repository->delete($model);
    }
}
