<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use Cycle\ORM\Select;
use Cycle\Database\Injection\Parameter;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of InvItem
 * @extends Select\Repository<TEntity>
 */
final class InvItemRepository extends Select\Repository
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
        $query = $this->select()
                      ->load(['tax_rate','product', 'task', 'inv']);
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

    /**
     * @return Sort
     */
    private function getSort(): Sort
    {
        return Sort::only(['id'])->withOrder(['id' => 'asc']);
    }

    /**
     * @param array|InvItem|null $invitem
     */
    public function save(array|InvItem|null $invitem): void
    {
        $this->entityWriter->write([$invitem]);
    }

    /**
     * @param array|InvItem|null $invitem
     */
    public function delete(array|InvItem|null $invitem): void
    {
        $this->entityWriter->delete([$invitem]);
    }

    /**
     * @param Select $query
     * @return EntityReader
     */
    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @param string $id
     * @return InvItem|null
     * @psalm-return TEntity|null
     */
    public function repoInvItemquery(string $id): InvItem|null
    {
        $query = $this->select()
                      ->load(['tax_rate','product', 'task', 'inv'])
                      ->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }

    /**
     * @param string $inv_id
     * @return EntityReader
     */
    public function repoInvItemIdquery(string $inv_id): EntityReader
    {
        $query = $this->select()
                     // ->load(['tax_rate','product', 'task', 'inv'])
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $inv_id
     * @return EntityReader
     */
    public function repoInvquery(string $inv_id): EntityReader
    {
        $query = $this->select()
                      ->load(['tax_rate','product', 'task', 'inv'])
                      ->where(['inv_id' => $inv_id]);
        return $this->prepareDataReader($query);
    }

    /**
     * @param string $inv_id
     * @return int
     */
    public function repoCount(string $inv_id): int
    {
        return $this->select()
                      ->where(['inv_id' => $inv_id])
                      ->count();
    }

    /**
     * @param string $id
     * @return int
     */
    public function repoInvItemCount(string $id): int
    {
        return $this->select()
                      ->where(['id' => $id])
                      ->count();
    }

    /**
     * @param array $item_ids
     * @return EntityReader
     */
    public function findinInvItems(array $item_ids): EntityReader
    {
        // Return empty result if no items are provided to avoid SQL syntax error
        if (empty($item_ids)) {
            return $this->prepareDataReader($this->select()->where('1=0'));
        }

        $query = $this->select()->where(['id' => ['in' => new Parameter($item_ids)]]);
        return $this->prepareDataReader($query);
    }
}
