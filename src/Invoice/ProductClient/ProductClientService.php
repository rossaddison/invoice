<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Infrastructure\Persistence\ProductClient\ProductClient;
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
    ): void {
        $product = 'product_id';
        if (isset($array[$product])) {
            $productEntity = $this->pR->repoProductquery((int) $array[$product]);
            if ($productEntity) {
                $model->setProduct($productEntity);
            }
        }
        $client = 'client_id';
        if (isset($array[$client])) {
            $model->setClient(
                $this->cR->repoClientquery((int) $array[$client]));
        }
    }

    /**
     * Associate all product IDs from an invoice's items with a client,
     * skipping pairs that already exist in product_client.
     *
     * @param int[] $productIds
     */
    public function syncFromInvItems(int $clientId, array $productIds): void
    {
        $today = (new \DateTimeImmutable())->format('Y-m-d');
        foreach ($productIds as $productId) {
            if (!$this->repository->isProductAssociatedWithClient($productId, $clientId)) {
                $this->save(new ProductClient(), [
                    'product_id' => $productId,
                    'client_id'  => $clientId,
                    'updated_at' => $today,
                ]);
            }
        }
    }

    /**
     * @param ProductClient $model
     */
    public function delete(ProductClient $model): void
    {
        $this->repository->delete($model);
    }
}
