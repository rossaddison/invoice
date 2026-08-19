<?php

declare(strict_types=1);

namespace App\Invoice\StockMovement;

use App\Infrastructure\Persistence\StockMovement\StockMovement;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of StockMovement
 * @extends Select\Repository<TEntity>
 */
final class StockMovementRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * @throws Throwable
     */
    public function save(StockMovement $movement): void
    {
        $this->entityWriter->write([$movement]);
    }

    /**
     * Full movement history for a product, most recent first.
     *
     * @psalm-return EntityReader
     */
    public function findAllForProduct(int $productId): EntityReader
    {
        $query = $this->select()->where(['product_id' => $productId]);
        return (new EntityReader($query))
            ->withSort(Sort::only(['id'])->withOrder(['id' => 'desc']));
    }

    /**
     * Current stock on hand for a product — the sum of every recorded
     * `quantity_delta`. Aggregated in PHP rather than a SQL SUM, matching
     * `RedirectClickRepository`'s own aggregation (Cycle's raw
     * query-builder aggregate API isn't a confirmed shape in this
     * codebase); a product's movement history is not expected to be large
     * enough for that to matter.
     */
    public function currentBalance(int $productId): float
    {
        $balance = 0.00;
        /** @var StockMovement $movement */
        foreach ($this->select()->where(['product_id' => $productId])->fetchAll() as $movement) {
            $balance += $movement->getQuantityDelta();
        }
        return $balance;
    }
}
