<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Invoice\Entity\ProductClient;

final class ProductClientService
{
    public function __construct(private ProductClientRepository $repository)
    {
    }

    /**
     * @param ProductClient $model
     * @param array $array
     */
    public function save(ProductClient $model, array $array): void
    {
        $model->nullifyRelationOnChange((int) $array['product_id']);

        $datetime_updated = new \DateTimeImmutable();
        $model->setUpdatedAt(
            $datetime_updated::createFromFormat('Y-m-d', (string) $array['updated_at'])
            ?: new \DateTimeImmutable('now'),
        );

        isset($array['product_id']) ? $model->setProductId((int) $array['product_id']) : '';
        isset($array['client_id']) ? $model->setClientId((int) $array['client_id']) : '';
        $this->repository->save($model);
    }

    /**
     * @param ProductClient $model
     */
    public function delete(ProductClient $model): void
    {
        $this->repository->delete($model);
    }
}
