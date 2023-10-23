<?php

declare(strict_types=1); 

namespace App\Invoice\CustomValue;

use App\Invoice\Entity\CustomValue;

final class CustomValueService
{
    private CustomValueRepository $repository;

    public function __construct(CustomValueRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param CustomValue $model
     * @param CustomValueForm $form
     * @return void
     */
    public function saveCustomValue(CustomValue $model, CustomValueForm $form): void
    { 
       null!== $form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       null!== $form->getValue() ? $model->setValue($form->getValue()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param array|CustomValue|null $model
     * @return void
     */
    public function deleteCustomValue(array|CustomValue|null $model): void
    {
       $this->repository->delete($model);
    }
}