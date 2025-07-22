<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;

final readonly class ProductCustomService
{
    public function __construct(private ProductCustomRepository $repository)
    {
    }

    public function saveProductCustom(ProductCustom $model, array $array): void
    {
        $array['product_id'] ? $model->setProduct_id((int) $array['product_id']) : '';
        $array['custom_field_id'] ? $model->setCustom_field_id((int) $array['custom_field_id']) : '';
        $array['value'] ? $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    public function deleteProductCustom(ProductCustom $model): void
    {
        $this->repository->delete($model);
    }
}
