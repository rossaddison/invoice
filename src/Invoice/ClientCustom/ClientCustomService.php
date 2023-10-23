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
     * @param ClientCustomForm $form
     * @return void
     */
    public function saveClientCustom(ClientCustom $model, ClientCustomForm $form): void
    {
       null!==$form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       null!==$form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       null!==$form->getValue() ? $model->setValue($form->getValue()) : '';       
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