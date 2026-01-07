<?php

declare(strict_types=1);

namespace App\Invoice\CategorySecondary;

use App\Invoice\Entity\CategorySecondary as CS;
use App\Invoice\CategoryPrimary\CategoryPrimaryRepository as cpR;

final readonly class CategorySecondaryService
{
    public function __construct(
        private CategorySecondaryRepository $repository,
        private cpR $cpR,
    ) {
    }

    public function saveCategorySecondary(
        CS $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['category_primary_id']) ?
            $model->setCategory_primary_id(
                (int) $array['category_primary_id']) : '';
        isset($array['name']) ?
            $model->setName((string) $array['name']) : '';
        $this->repository->save($model);
    }
    
    private function persist(CS $model, array $array): CS
    {
        $cp = 'category_primary_id';
        if (isset($array[$cp])) {
            $model->setCategoryPrimary(
                $this->cpR->repoCategoryPrimaryQuery(
                    (string) $array[$cp]));
        }
        return $model;
    }

    public function deleteCategorySecondary(CS $model): void
    {
        $this->repository->delete($model);
    }
}
