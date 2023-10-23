<?php

declare(strict_types=1); 

namespace App\Invoice\UnitPeppol;

use App\Invoice\Entity\UnitPeppol;


final class UnitPeppolService
{

    private UnitPeppolRepository $repository;

    public function __construct(UnitPeppolRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveUnitPeppol(UnitPeppol $model, UnitPeppolForm $form): void
    {
        
   null!==$form->getUnit_id() ? $model->setUnit_id($form->getUnit_id()) : '';
   null!==$form->getCode() ? $model->setCode($form->getCode()) : '';
   null!==$form->getName() ? $model->setName($form->getName()) : '';
   null!==$form->getDescription() ? $model->setDescription($form->getDescription()) : '';
 
        $this->repository->save($model);
    }
    
    public function deleteUnitPeppol(UnitPeppol $model): void
    {
        $this->repository->delete($model);
    }
}