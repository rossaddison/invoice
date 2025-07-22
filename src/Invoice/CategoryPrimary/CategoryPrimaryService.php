<?php

declare(strict_types=1);

namespace App\Invoice\CategoryPrimary;

use App\Invoice\Entity\CategoryPrimary;

final readonly class CategoryPrimaryService
{
    public function __construct(private CategoryPrimaryRepository $repository) {}

    public function saveCategoryPrimary(CategoryPrimary $model, array $array): void
    {
        isset($array['name']) ? $model->setName((string) $array['name']) : '';

        $this->repository->save($model);
    }

    public function deleteCategoryPrimary(CategoryPrimary $model): void
    {
        $this->repository->delete($model);
    }
}
