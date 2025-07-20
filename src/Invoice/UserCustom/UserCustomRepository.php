<?php

declare(strict_types=1);

namespace App\Invoice\UserCustom;

use App\Invoice\Entity\UserCustom;
use Cycle\ORM\Select;
use Throwable;
use Yiisoft\Data\Reader\Sort;
use Yiisoft\Data\Cycle\Reader\EntityReader;
use Yiisoft\Data\Cycle\Writer\EntityWriter;

/**
 * @template TEntity of UserCustom
 * @extends Select\Repository<TEntity>
 */
final class UserCustomRepository extends Select\Repository
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
     * Get usercustoms  without filter
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
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserCustom|null $usercustom
     * @throws Throwable
     */
    public function save(array|UserCustom|null $usercustom): void
    {
        $this->entityWriter->write([$usercustom]);
    }

    /**
     * @see Reader/ReadableDataInterface|InvalidArgumentException
     * @param array|UserCustom|null $usercustom
     * @throws Throwable
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
     * @return UserCustom|null
     *
     * @psalm-return TEntity|null
     */
    public function repoUserCustomquery(string $id): UserCustom|null
    {
        $query = $this->select()->load('user')->where(['id' => $id]);
        return  $query->fetchOne() ?: null;
    }
}
