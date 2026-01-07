<?php

declare(strict_types=1);

namespace App\Invoice\ProductProperty;

use App\Invoice\Entity\ProductProperty;
use App\Invoice\Product\ProductRepository as PR;

final readonly class ProductPropertyService
{
    public function __construct(
        private ProductPropertyRepository $repository,
        private PR $pR,
    ) {
    }

    /**
     * @param ProductProperty $model
     * @param array $array
     */
    public function saveProductProperty(
        ProductProperty $model,
        array $array
    ): void {
        $this->persist($model, $array);
        isset($array['product_id']) ? 
            $model->setProduct_id(
                (int) $array['product_id']) : '';
        isset($array['name']) ? 
            $model->setName((string) $array['name']) : '';
        isset($array['value']) ? 
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    private function persist(
        ProductProperty $model,
        array $array
    ): ProductProperty {
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery(
                (string) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        return $model;
    }

    /**
     * @param ProductProperty $model
     */
    public function deleteProductProperty(ProductProperty $model): void
    {
        $this->repository->delete($model);
    }
}
