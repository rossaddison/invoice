<?php

declare(strict_types=1); 

namespace App\Invoice\ClientCustom;

use App\Invoice\Entity\ClientCustom;

final class ClientCustomService
{
    private ClientCustomRepository $repository;

    public function __construct(ClientCustomRepository $repository)
    {
       $this->repository = $repository;
    }

    /**
     * 
     * @param ClientCustom $model
     * @param array $array
     * @return void
     */
    public function saveClientCustom(ClientCustom $model, array $array): void
    {
       null!==$array['client_id'] ? $model->setClient_id((int)$array['client_id']) : '';
       null!==$array['custom_field_id'] ? $model->setCustom_field_id((int)$array['custom_field_id']) : '';
       null!==$array['value'] ? $model->setValue((string)$array['value']) : '';       
       $this->repository->save($model);
    }
    
    /**
     * @param ClientCustom $model
     * @return void
     */
    public function deleteClientCustom(ClientCustom $model): void
    {
        $this->repository->delete($model);
    }
}