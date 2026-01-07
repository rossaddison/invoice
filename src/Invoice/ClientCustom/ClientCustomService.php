<?php

declare(strict_types=1);

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\CustomField\CustomFieldRepository as CFR;

final readonly class ClientCustomService
{
    public function __construct(
        private ClientCustomRepository $repository,
        private CR $cR,
        private CFR $cfR,
    ) {
    }

    /**
     * @param ClientCustom $model
     * @param array $array
     */
    public function saveClientCustom(
        ClientCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['client_id']) ?
            $model->setClient_id((int) $array['client_id']) : '';
        isset($array['custom_field_id']) ?
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        isset($array['value']) ?
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }
    
    private function persist(ClientCustom $model, array $array): ClientCustom
    {
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery((string) $array[$client]));
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
     * @param ClientCustom $model
     */
    public function deleteClientCustom(ClientCustom $model): void
    {
        $this->repository->delete($model);
    }
}
