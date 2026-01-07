<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Invoice\Entity\ProductClient;
use App\Invoice\Client\ClientRepository as CR;
use App\Invoice\Product\ProductRepository as PR;

final class ProductClientService
{
    public function __construct(
        private ProductClientRepository $repository,
        private PR $pR,
        private CR $cR,
    ) {
    }

    /**
     * @param ProductClient $model
     * @param array $array
     */
    public function save(
        ProductClient $model,
        array $array
    ): void {
        $this->persist($model, $array);

        $datetime_updated = new \DateTimeImmutable();
        $model->setUpdatedAt(
            $datetime_updated::createFromFormat(
                'Y-m-d', 
                (string) $array['updated_at'])
            ?: new \DateTimeImmutable('now'),
        );

        isset($array['product_id']) ? 
            $model->setProductId(
                (int) $array['product_id']) : '';
        isset($array['client_id']) ? 
            $model->setClientId(
                (int) $array['client_id']) : '';
        $this->repository->save($model);
    }

    private function persist(
        ProductClient $model,
        array $array
    ): ProductClient {
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery(
                (string) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery(
                    (string) $array[$client]));
        }
        return $model;
    }

    /**
     * @param ProductClient $model
     */
    public function delete(ProductClient $model): void
    {
        $this->repository->delete($model);
    }
}
