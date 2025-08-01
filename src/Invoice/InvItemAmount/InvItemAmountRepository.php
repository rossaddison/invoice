<?php

declare(strict_types=1);

namespace App\Invoice\InvItemAmount;

use App\Invoice\Entity\InvItemAmount;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvItemAmount
 * @extends Select\Repository<TEntity>
 */
final class InvItemAmountRepository extends Select\Repository
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
     * Get invitemamounts  without filter
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select()
                      ->load('inv_item');
        return $this->prepareDataReader($query);
    }

    /**
     * @psalm-return EntityReader
     */
    public function getReader(): EntityReader
    {
        return (new EntityReader($this->select()))
            ->withSort($this->getSort());
    }

    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvItemAmount|null $invitemamount
     * @throws Throwable
     */
    public function save(array|InvItemAmount|null $invitemamount): void
    {
        $this->entityWriter->write([$invitemamount]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|InvItemAmount|null $invitemamount
     * @throws Throwable
     */
    public function delete(array|InvItemAmount|null $invitemamount): void
    {
        $this->entityWriter->delete([$invitemamount]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @return InvItemAmount|null
     *
     * @psalm-return TEntity|null
     */
    public function repoInvItemAmountquery(string $inv_item_id): InvItemAmount|null
    {
        $query = $this->select()
                      ->load(['inv_item'])
                      ->where(['inv_item_id' => $inv_item_id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $inv_item_id
     * @return int
     */
    public function repoCount(string $inv_item_id): int
    {
        $query = $this->select()
                      ->where(['inv_item_id' => $inv_item_id]);
        return $query->count();
    }
}
