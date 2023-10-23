<?php

declare(strict_types=1); 

namespace App\Invoice\FromDropDown;

use App\Invoice\Entity\FromDropDown;

final class FromDropDownService
{
    private FromDropDownRepository $repository;

    public function __construct(FromDropDownRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveFromDropDown(FromDropDown $model, FromDropDownForm $form): void
    {
      null!==$form->getId() ? $model->setId($form->getId()) : '';
      null!==$form->getEmail() ? $model->setEmail($form->getEmail()) : '';
      null!==$form->getInclude() ? $model->setInclude($form->getInclude()) : '';
      null!==$form->getDefault_email() ? $model->setDefault_email($form->getDefault_email()) : '';
      $this->repository->save($model);
    }
    
    public function deleteFromDropDown(FromDropDown $model): void
    {
        $this->repository->delete($model);
    }
}