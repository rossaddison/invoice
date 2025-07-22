<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;

final readonly class ProductPropertyService
{
    public function __construct(private ProductPropertyRepository $repository)
    {
    }

    public function saveProductProperty(ProductProperty $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['product_id']);
        isset($array['product_id']) ? $model->setProduct_id((int) $array['product_id']) : '';
        isset($array['name']) ? $model->setName((string) $array['name']) : '';
        isset($array['value']) ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    public function deleteProductProperty(ProductProperty $model): void
    {
        $this->repository->delete($model);
    }
}
