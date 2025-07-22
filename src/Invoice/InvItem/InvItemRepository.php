<?php

declare(strict_types=1);

namespace App\Invoice\InvItem;

use App\Invoice\Entity\InvItem;
use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of InvItem
 *
 * @extends Select\Repository<TEntity>
 */
final class InvItemRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
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
            ->load(['tax_rate', 'product', 'task', 'inv']);

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

    public function save(array|InvItem|null $invitem): void
    {
        $this->entityWriter->write([$invitem]);
    }

    public function delete(array|InvItem|null $invitem): void
    {
        $this->entityWriter->delete([$invitem]);
    }

    private function prepareDataReader(Select $query): EntityReader
    {
        return (new EntityReader($query))->withSort(
            Sort::only(['id'])
                ->withOrder(['id' => 'asc']),
        );
    }

    /**
     * @psalm-return TEntity|null
     */
    public function repoInvItemquery(string $id): ?InvItem
    {
        $query = $this->select()
            ->load(['tax_rate', 'product', 'task', 'inv'])
            ->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }

    public function repoInvItemIdquery(string $inv_id): EntityReader
    {
        $query = $this->select()
                     // ->load(['tax_rate','product', 'task', 'inv'])
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    public function repoInvquery(string $inv_id): EntityReader
    {
        $query = $this->select()
            ->load(['tax_rate', 'product', 'task', 'inv'])
            ->where(['inv_id' => $inv_id]);

        return $this->prepareDataReader($query);
    }

    public function repoCount(string $inv_id): int
    {
        return $this->select()
            ->where(['inv_id' => $inv_id])
            ->count();
    }

    public function repoInvItemCount(string $id): int
    {
        return $this->select()
            ->where(['id' => $id])
            ->count();
    }

    public function findinInvItems(array $item_ids): EntityReader
    {
        $query = $this->select()->where(['id' => ['in' => new Parameter($item_ids)]]);

        return $this->prepareDataReader($query);
    }
}
