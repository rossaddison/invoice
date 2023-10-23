<?php

declare(strict_types=1); 

namespace App\Invoice\SalesOrderCustom;

use App\Invoice\Entity\SalesOrderCustom;

final class SalesOrderCustomService
{
    private SalesOrderCustomRepository $repository;

    public function __construct(SalesOrderCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param SalesOrderCustom $model
     * @param SalesOrderCustomForm $form
     * @return void
     */
    public function saveSoCustom(SalesOrderCustom $model, SalesOrderCustomForm $form): void
    { 
       null!==$form->getSo_id() ? $model->setSo_id($form->getSo_id()) : '';
       null!==$form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       $model->setValue($form->getValue() ?? '');
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|SalesOrderCustom|null $model
     * @return void
     */
    public function deleteSalesOrderCustom(array|SalesOrderCustom|null $model): void
    {
        $this->repository->delete($model);
    }
}