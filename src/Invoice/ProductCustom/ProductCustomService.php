<?php

declare(strict_types=1);

namespace App\Invoice\ProductCustom;

use App\Invoice\Entity\ProductCustom;
use App\Invoice\CustomField\CustomFieldRepository as CFR;
use App\Invoice\Product\ProductRepository as PR;

final readonly class ProductCustomService
{
    public function __construct(
        private ProductCustomRepository $repository,
        private PR $pR,
        private CFR $cfR,
    ) {
    }

    /**
     * @param ProductCustom $model
     * @param array $array
     */
    public function saveProductCustom(
        ProductCustom $model,
        array $array
    ): void {
        $this->persist($model, $array);
        $array['product_id'] ? 
            $model->setProduct_id(
                (int) $array['product_id']) : '';
        $array['custom_field_id'] ? 
            $model->setCustom_field_id(
                (int) $array['custom_field_id']) : '';
        $array['value'] ? 
            $model->setValue((string) $array['value']) : '';
        $this->repository->save($model);
    }

    private function persist(
        ProductCustom $model,
        array $array
    ): ProductCustom {
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery(
                (string) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        $custom_field = 'custom_field_id';
        if (isset($array[$custom_field])) {
            $model->setCustomField(
                $this->cfR->repoCustomFieldquery(
                    (string) $array[$custom_field]));
        }
        return $model;
    }

    /**
     * @param ProductCustom $model
     */
    public function deleteProductCustom(ProductCustom $model): void
    {
        $this->repository->delete($model);
    }
}
