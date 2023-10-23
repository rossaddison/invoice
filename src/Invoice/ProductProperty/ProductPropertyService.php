<?php

declare(strict_types=1); 

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;
use App\Invoice\ProductProperty\ProductPropertyRepository;


final class ProductPropertyService
{

    private ProductPropertyRepository $repository;

    public function __construct(ProductPropertyRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveProductProperty(ProductProperty $model, ProductPropertyForm $form): void
    {
        
   null!==$form->getProduct_id() ? $model->setProduct_id($form->getProduct_id()) : '';
   null!==$form->getName() ? $model->setName($form->getName()) : '';
   null!==$form->getValue() ? $model->setValue($form->getValue()) : '';
 
        $this->repository->save($model);
    }
    
    public function deleteProductProperty(ProductProperty $model): void
    {
        $this->repository->delete($model);
    }
}