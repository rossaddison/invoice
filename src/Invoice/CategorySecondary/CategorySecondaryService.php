<?php

declare(strict_types=1); 

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary;


final class CategorySecondaryService
{

    private CategorySecondaryRepository $repository;

    public function __construct(CategorySecondaryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function saveCategorySecondary(CategorySecondary $model, array $array): void
    {
        isset($array['category_primary_id']) ? $model->setCategory_primary_id((int)$array['category_primary_id']) : '';
        isset($array['name']) ? $model->setName((string)$array['name']) : '';
        $this->repository->save($model);
    }
    
    public function deleteCategorySecondary(CategorySecondary $model): void
    {
        $this->repository->delete($model);
    }
}