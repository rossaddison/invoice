<?php

declare(strict_types=1);

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;
use Cycle\ORM\Select;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;
use Yiisoft\Data\Reader\Sort;

/**
 * @template TEntity of UserCustom
 *
 * @extends Select\Repository<TEntity>
 */
final class UserCustomRepository extends Select\Repository
{
    /**
     * @param Select<TEntity> $select
     */
    public function __construct(Select $select, private readonly EntityWriter $entityWriter)
    {
        parent::__construct($select);
    }

    /**
     * Get usercustoms  without filter.
     *
     * @psalm-return EntityReader
     */
    public function findAllPreloaded(): EntityReader
    {
        $query = $this->select();

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
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function save(array|UserCustom|null $usercustom): void
    {
        $this->entityWriter->write([$usercustom]);
    }

    /**
     * Related logic: see Reader/ReadableDataInterface|InvalidArgumentException.
     *
     * @throws \Throwable
     */
    public function delete(array|UserCustom|null $usercustom): void
    {
        $this->entityWriter->delete([$usercustom]);
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
    public function repoUserCustomquery(string $id): ?UserCustom
    {
        $query = $this->select()->load('user')->where(['id' => $id]);

        return $query->fetchOne() ?: null;
    }
}
