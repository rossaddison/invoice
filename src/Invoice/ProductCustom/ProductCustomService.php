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
     * @param array $array
     * @return void
     */
    public function saveProductCustom(ProductCustom $model, array $array): void
    { 
       $array['product_id'] ? $model->setProduct_id((int)$array['product_id']) : '';
       $array['custom_field_id'] ? $model->setCustom_field_id((int)$array['custom_field_id']) : '';
       $array['value'] ? $model->setValue((string)$array['value']) : '';
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