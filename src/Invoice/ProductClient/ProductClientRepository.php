<?php

declare(strict_types=1);

namespace App\Invoice\ProductClient;

use App\Infrastructure\Persistence\ProductClient\ProductClient;
use Cycle\ORM\Select;
use Yiisoft\Data\Reader\Sort;
use Throwable;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use DateTimeImmutable;

/**
 * @template TEntity of ProductClient
 * @extends Select\Repository<TEntity>
 */
final class ProductClientRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     * @param EntityWriter $entityWriter
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }



    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ProductClient|null $productclient
     * @throws Throwable
     */
    public function save(array|ProductClient|null $productclient): void
    {
        $this->entityWriter->write([$productclient]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|ProductClient|null $productclient
     * @throws Throwable
     */
    public function delete(array|ProductClient|null $productclient): void
    {
        $this->entityWriter->delete([$productclient]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * Get productclients  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('product');
        return $this->prepareDataReader($query);
    }

    public function getCreatedAt(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->created_at
         */
        return $this->created_at;
    }

    public function getUpdatedAt(): string|DateTimeImmutable|null
    {
        /**
         * @var DateTimeImmutable|string|null $this->updated_at
         */
        return $this->updated_at;
    }

    public function repoProductClientQuery(int $id): ?ProductClient
    {
        $query = $this->select()
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }


    /**
     * Find all product-client associations for a specific client
     *
     * findAll() is typed iterable (a lazy result set), so it is materialised here to honour
     * the array return type rather than relying on how a given Psalm release treats iterable.
     *
     * @return list<ProductClient>
     */
    public function findByClientId(int $clientId): array
    {
        return iterator_to_array($this->findAll(['client_id' => $clientId]), false);
    }

    /**
     * Find all client associations for a specific product
     * @return list<ProductClient>
     */
    public function findByProductId(int $productId): array
    {
        return iterator_to_array($this->findAll(['product_id' => $productId]), false);
    }

    /**
     * Check if a product is associated with a specific client
     */
    public function isProductAssociatedWithClient(int $productId, int $clientId): bool
    {
        return $this->findOne(['product_id' => $productId, 'client_id' => $clientId]) !== null;
    }

    /**
     * Get specific association
     */
    public function findByProductAndClient(int $productId, int $clientId): ?ProductClient
    {
        return $this->findOne(['product_id' => $productId, 'client_id' => $clientId]);
    }
}
