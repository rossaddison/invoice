<?php

declare(strict_types=1);

namespace App\Invoice\SquareMerchant;

use App\Infrastructure\Persistence\SquareMerchant\SquareMerchant;
// Cycle
use Cycle\ORM\Select;
// Yiisoft
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Throwable;

/**
 * @template TEntity of SquareMerchant
 * @extends Select\Repository<TEntity>
 */
final class SquareMerchantRepository extends Select\Repository
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
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();
        return $this->prepareDataReader($query);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SquareMerchant|null $squareMerchant
     * @throws Throwable
     */
    public function save(array|SquareMerchant|null $squareMerchant): void
    {
        $this->entityWriter->write([$squareMerchant]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|SquareMerchant|null $squareMerchant
     * @throws Throwable
     */
    public function delete(array|SquareMerchant|null $squareMerchant): void
    {
        $this->entityWriter->delete([$squareMerchant]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * The most recent successful Square payment audit record for this
     * invoice, used to look up the payment_id a refund must be issued
     * against — mirrors MerchantRepository::
     * repoMerchantLatestSuccessfulByInvIdAndDriver(), minus the driver
     * filter (this table is Square-only by construction).
     */
    public function repoSquareMerchantLatestSuccessfulByInvId(int $invId): ?SquareMerchant
    {
        $query = $this->select()
                      ->where(['inv_id' => $invId])
                      ->andWhere(['successful' => true])
                      ->orderBy('id', 'DESC');
        return $query->fetchOne() ?: null;
    }
}
