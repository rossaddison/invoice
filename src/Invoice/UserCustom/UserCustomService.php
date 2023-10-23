<?php

declare(strict_types=1); 

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;
use App\Invoice\UserCustom\UserCustomRepository;
use App\Invoice\UserCustom\UserCustomForm;

final class UserCustomService
{
    private UserCustomRepository $repository;

    public function __construct(UserCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param UserCustom $model
     * @param UserCustomForm $form
     * @return void
     */
    public function saveUserCustom(UserCustom $model, UserCustomForm $form): void
    {
       if (null!==$form->getUser_id()&& null!==$form->getFieldid()) {
        $model->setUser_id($form->getUser_id());
        $model->setFieldid($form->getFieldid());
        $model->setFieldvalue($form->getFieldvalue() ?? '');
        $this->repository->save($model);
       }
    }
    
    /**
     * 
     * @param UserCustom $model
     * @return void
     */
    public function deleteUserCustom(UserCustom $model): void
    {
        $this->repository->delete($model);
    }
}