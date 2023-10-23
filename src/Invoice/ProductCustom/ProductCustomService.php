<?php

declare(strict_types=1); 

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;

final class ProductCustomService
{
    private ProductCustomRepository $repository;

    public function __construct(ProductCustomRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * 
     * @param ProductCustom $model
     * @param ProductCustomForm $form
     * @return void
     */
    public function saveProductCustom(ProductCustom $model, ProductCustomForm $form): void
    { 
       $form->getProduct_id() ? $model->setProduct_id($form->getProduct_id()) : '';
       $form->getCustom_field_id() ? $model->setCustom_field_id($form->getCustom_field_id()) : '';
       $form->getValue() ? $model->setValue($form->getValue()) : '';
       $this->repository->save($model);
    }
    
    /**
     * 
     * @param ProductCustom $model
     * @return void
     */
    public function deleteProductCustom(ProductCustom $model): void
    {
        $this->repository->delete($model);
    }
}