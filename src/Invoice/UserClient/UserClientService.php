<?php

declare(strict_types=1); 

namespace App\Invoice\UserClient;

use App\Invoice\Entity\UserClient;

final class UserClientService
{
    private UserClientRepository $repository;

    public function __construct(UserClientRepository $repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * 
     * @param UserClient $model
     * @param UserClientForm $form
     * @return void
     */
    public function saveUserClient(UserClient $model, UserClientForm $form): void
    {        
       $form->getUser_id() ? $model->setUser_id($form->getUser_id()) : '';
       $form->getClient_id() ? $model->setClient_id($form->getClient_id()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param UserClient $model
     * @return void
     */
    public function deleteUserClient(UserClient $model): void
    {       
       $this->repository->delete($model);
    }
}