<?php

declare(strict_types=1);

namespace App\Invoice\Unit;

use App\Invoice\Entity\Unit;

final class UnitService
{
    private UnitRepository $repository;

    public function __construct(UnitRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param Unit $model
     * @param UnitForm $form
     * @return void
     */
    public function saveUnit(Unit $model, UnitForm $form): void
    {
        $form->getUnit_name() ? $model->setUnit_name($form->getUnit_name()) : '';
        $form->getUnit_name_plrl() ? $model->setUnit_name_plrl($form->getUnit_name_plrl()) : '';
        $this->repository->save($model);
    }
    
    /**
     * 
     * @param Unit $model
     * @return void
     */
    public function deleteUnit(Unit $model): void
    {
        $this->repository->delete($model);
    }
}
